<?php

namespace App\Providers;

use App\Contracts\AIChatProvider;
use App\Contracts\GoogleTranslationClient;
use App\Contracts\RecommendationProvider;
use App\Contracts\TranslationProvider;
use App\Models\Permission;
use App\Models\User;
use App\Services\AIChat\GeminiAIChatProvider;
use App\Services\AIChat\NullAIChatProvider;
use App\Services\Recommendation\GeminiRecommendationProvider;
use App\Services\Recommendation\NullRecommendationProvider;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use App\Services\SystemSetting\SystemSettingCatalog;
use App\Services\Translation\GoogleCloudTranslationClient;
use App\Services\Translation\GoogleCloudTranslationProvider;
use App\Services\Translation\NullTranslationProvider;
use App\Services\Translation\TranslationCallBudget;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TranslationCallBudget::class);
        $this->app->scoped(TypedSystemSettingResolver::class);
        $this->app->singleton(GoogleTranslationClient::class, GoogleCloudTranslationClient::class);
        $this->app->bind(AIChatProvider::class, function ($app): AIChatProvider {
            try {
                $gemini = $app->make(TypedSystemSettingResolver::class)->gemini();
                if ($gemini['enabled'] && is_string($gemini['api_key']) && $gemini['api_key'] !== '') {
                    return $app->make(GeminiAIChatProvider::class);
                }
            } catch (Throwable) {
                // The fallback provider keeps installation and migration commands usable.
            }

            return $app->make(NullAIChatProvider::class);
        });
        $this->app->bind(RecommendationProvider::class, function ($app): RecommendationProvider {
            $gemini = $app->make(TypedSystemSettingResolver::class)->gemini();
            $apiKey = $gemini['api_key'];
            $model = $gemini['model'];
            $baseUrl = config('recommendation.gemini.base_url');

            if (
                config('features.recommendation') &&
                config('recommendation.ai_enabled') && $gemini['enabled'] &&
                is_string($apiKey) && $apiKey !== '' &&
                is_string($model) && $model !== '' &&
                is_string($baseUrl) && $baseUrl !== ''
            ) {
                return $app->make(GeminiRecommendationProvider::class);
            }

            return $app->make(NullRecommendationProvider::class);
        });
        $this->app->bind(TranslationProvider::class, function ($app): TranslationProvider {
            try {
                if (
                    Schema::hasTable('system_settings') &&
                    $app->make(TypedSystemSettingResolver::class)->googleTranslation() !== null
                ) {
                    return $app->make(GoogleCloudTranslationProvider::class);
                }
            } catch (Throwable) {
                // Installation and migration commands must remain usable before the settings table exists.
            }
            $provider = config('translation.provider');
            $project = config('translation.google.project_id');
            $credentials = config('translation.google.credentials');
            if (
                $provider === 'google' &&
                is_string($project) &&
                $project !== '' &&
                is_string($credentials) &&
                $credentials !== '' &&
                is_file($credentials)
            ) {
                return $app->make(GoogleCloudTranslationProvider::class);
            }

            return $app->make(NullTranslationProvider::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['admin.*', 'customer.*'], function ($view): void {
            $settings = [];
            $aiChatEnabled = (bool) config('features.ai_chat');
            try {
                if (Schema::hasTable('system_settings')) {
                    $resolver = app(TypedSystemSettingResolver::class);
                    $settings = $resolver->publicSiteSettings();
                    $aiChatEnabled = $resolver->gemini()['enabled'];
                }
            } catch (Throwable) {
                // Views used during installation keep their bundled brand fallbacks.
            }
            $logoPath = $settings[SystemSettingCatalog::SITE_LOGO] ?? null;
            $bannerPath = $settings[SystemSettingCatalog::SITE_BANNER] ?? null;
            $view->with([
                'configuredSiteName' => $settings[SystemSettingCatalog::SITE_NAME] ?? 'Beer Garden',
                'configuredSiteLogoUrl' => $logoPath
                    ? Storage::disk('public')->url($logoPath)
                    : asset('images/brand/quan-89-logo.png'),
                'configuredSiteBannerUrl' => $bannerPath ? Storage::disk('public')->url($bannerPath) : null,
                'aiChatEnabled' => $aiChatEnabled,
            ]);
        });

        RateLimiter::for('ai-chat', function (Request $request): Limit|array {
            if ($request->user() !== null) {
                return Limit::perMinute((int) config('ai_chat.limits.authenticated_per_minute', 30))
                    ->by('ai-chat-user:'.$request->user()->getAuthIdentifier());
            }

            $limit = (int) config('ai_chat.limits.guest_per_minute', 20);

            return [
                Limit::perMinute($limit)->by('ai-chat-ip:'.$request->ip()),
                Limit::perMinute($limit)->by('ai-chat-session:'.$request->session()->getId()),
            ];
        });

        foreach (array_keys(Permission::CATALOG) as $permission) {
            $isContextPermission = in_array($permission, Permission::INTERNAL_CONTEXT_PERMISSIONS, true);

            Gate::define(
                $permission,
                fn (User $user): bool => $user->hasPermission($permission) &&
                    (! $isContextPermission || $user->hasActiveEmployee()),
            );
        }
    }
}
