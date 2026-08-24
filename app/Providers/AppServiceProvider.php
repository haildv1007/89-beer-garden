<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
