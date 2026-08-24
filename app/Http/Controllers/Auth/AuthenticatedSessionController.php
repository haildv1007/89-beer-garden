<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CaptureRelativeIntendedUrl;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        try {
            $updated = $request->user()
                ->forceFill(['last_login_at' => now()])
                ->save();

            if (! $updated) {
                throw new RuntimeException('Unable to update login tracking.');
            }
        } catch (Throwable $exception) {
            $this->logoutAndInvalidateSession($request);

            throw $exception;
        }

        return $this->redirectToSafeIntendedPath($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logoutAndInvalidateSession($request);

        return redirect()->route('customer.home');
    }

    private function redirectToSafeIntendedPath(Request $request): RedirectResponse
    {
        $fallback = '/';
        $capturedIntended = $request->session()->pull(CaptureRelativeIntendedUrl::SESSION_KEY);
        $frameworkIntended = $request->session()->pull('url.intended');
        $intended = $capturedIntended ?? $frameworkIntended;

        if (! $this->isSafeRelativePath($intended)) {
            return new RedirectResponse($fallback);
        }

        return new RedirectResponse($intended);
    }

    private function isSafeRelativePath(mixed $destination): bool
    {
        if (! is_string($destination) || $destination === '' || $destination[0] !== '/') {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $destination) === 1) {
            return false;
        }

        $decoded = $destination;

        do {
            $decodedDestination = rawurldecode($decoded);
            $changed = $decodedDestination !== $decoded;
            $decoded = $decodedDestination;
        } while ($changed);

        if (str_starts_with($decoded, '//') || str_contains($decoded, '\\')) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
            return false;
        }

        $parts = parse_url($destination);

        return $parts !== false
            && ! isset($parts['scheme'])
            && ! isset($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    private function logoutAndInvalidateSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
