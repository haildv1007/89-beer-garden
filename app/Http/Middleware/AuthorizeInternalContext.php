<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeInternalContext
{
    /**
     * Enforce a known internal context, active Employee, and mapped permission.
     */
    public function handle(Request $request, Closure $next, ?string $context = null): Response
    {
        $permission = Permission::INTERNAL_CONTEXT_PERMISSIONS[$context] ?? null;

        abort_unless($permission !== null, Response::HTTP_FORBIDDEN);

        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user?->hasActiveEmployee() && $user->can($permission), Response::HTTP_FORBIDDEN);

        // Employee-facing operational contexts are intentionally Vietnamese-only.
        // Keep the customer's language preference in session for their next public visit.
        app()->setLocale('vi');

        return $next($request);
    }
}
