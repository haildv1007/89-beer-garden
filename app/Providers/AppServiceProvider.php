<?php

namespace App\Providers;

use App\Contracts\GoogleTranslationClient;
use App\Contracts\TranslationProvider;
use App\Models\Permission;
use App\Models\User;
use App\Services\Translation\GoogleCloudTranslationClient;
use App\Services\Translation\GoogleCloudTranslationProvider;
use App\Services\Translation\NullTranslationProvider;
use App\Services\Translation\TranslationCallBudget;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TranslationCallBudget::class);
        $this->app->singleton(GoogleTranslationClient::class, GoogleCloudTranslationClient::class);
        $this->app->bind(TranslationProvider::class, function ($app): TranslationProvider {
            $provider = config('translation.provider');
            $project = config('translation.google.project_id');
            $credentials = config('translation.google.credentials');
            if ($provider === 'google' && is_string($project) && $project !== ''
                && is_string($credentials) && $credentials !== '' && is_file($credentials)) {
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
        foreach (array_keys(Permission::CATALOG) as $permission) {
            $isContextPermission = in_array(
                $permission,
                Permission::INTERNAL_CONTEXT_PERMISSIONS,
                true,
            );

            Gate::define(
                $permission,
                fn (User $user): bool => $user->hasPermission($permission)
                    && (! $isContextPermission || $user->hasActiveEmployee()),
            );
        }
    }
}
