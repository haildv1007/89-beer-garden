<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Temporary fail-closed boundary. Replace this middleware with the approved
 * context authorization implementation in Phase 4.2.
 */
class DenyInternalContextUntilAuthorizationIsImplemented
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort(Response::HTTP_FORBIDDEN);
    }
}
