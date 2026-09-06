<?php

namespace App\Providers;

use App\Contracts\GoogleTranslationClient;
use App\Contracts\TranslationProvider;
use App\Models\Permission;
use App\Models\User;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use App\Services\Translation\GoogleCloudTranslationClient;
use App\Services\Translation\GoogleCloudTranslationProvider;
use App\Services\Translation\NullTranslationProvider;
use App\Services\Translation\TranslationCallBudget;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
