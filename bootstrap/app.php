<?php

use App\Http\Middleware\CaptureRelativeIntendedUrl;
use App\Http\Middleware\DenyInternalContextUntilAuthorizationIsImplemented;
use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware([
                'web',
                CaptureRelativeIntendedUrl::class,
                'auth',
                DenyInternalContextUntilAuthorizationIsImplemented::class,
            ])
                ->prefix('pos')
                ->name('pos.')
                ->group(base_path('routes/pos.php'));

            Route::middleware([
                'web',
                CaptureRelativeIntendedUrl::class,
                'auth',
                DenyInternalContextUntilAuthorizationIsImplemented::class,
            ])
                ->prefix('kitchen')
                ->name('kitchen.')
                ->group(base_path('routes/kitchen.php'));

            Route::middleware([
                'web',
                CaptureRelativeIntendedUrl::class,
                'auth',
                DenyInternalContextUntilAuthorizationIsImplemented::class,
            ])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [EnsureAccountIsActive::class]);
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            CaptureRelativeIntendedUrl::class,
        );

        $middleware->trustHosts(
            at: function (): array {
                $host = parse_url((string) config('app.url'), PHP_URL_HOST);

                return is_string($host) && $host !== ''
                    ? ['^'.preg_quote($host).'$']
                    : [];
            },
            subdomains: false,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
