<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetApplicationLocale
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('locale')) {
            $locale = (string) $request->session()->get('locale');
            app()->setLocale(in_array($locale, config('localization.supported_locales'), true) ? $locale : 'vi');
        }

        return $next($request);
    }
}
