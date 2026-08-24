<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureRelativeIntendedUrl
{
    public const SESSION_KEY = 'auth.relative_intended';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() && $request->isMethod('GET')) {
            $destination = $request->getPathInfo();
            $query = $request->getQueryString();

            $request->session()->put(
                self::SESSION_KEY,
                $query === null ? $destination : $destination.'?'.$query,
            );
        }

        return $next($request);
    }
}
