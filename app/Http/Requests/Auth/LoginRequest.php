<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Security\SecurityEventLogger;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use App\Support\PhoneNumber;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['nullable', 'required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:login', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $protection = app(TypedSystemSettingResolver::class)->loginProtection();
        $this->ensureIsNotRateLimited();

        $identifier = trim($this->string($this->filled('login') ? 'login' : 'email')->toString());
        $phone = PhoneNumber::normalize($identifier);
        $isPhone = preg_match('/^0\d{9}$/', $phone) === 1;
        if ($isPhone) {
            $users = User::query()
                ->where('status', User::STATUS_ACTIVE)
                ->where(function ($query) use ($phone): void {
                    $query
                        ->where('phone', $phone)
                        ->orWhereHas(
                            'customer',
                            fn ($customer) => $customer->whereIn('phone', PhoneNumber::variants($phone)),
                        );
                })
                ->limit(2)
                ->get();
            $user = $users->count() === 1 ? $users->first() : null;
            $authenticated = $user !== null && Hash::check($this->string('password')->toString(), $user->password);
            if ($authenticated) {
                Auth::login($user);
            }
        } else {
            $authenticated = Auth::attempt([
                'email' => mb_strtolower($identifier),
                'password' => $this->string('password')->toString(),
                'status' => User::STATUS_ACTIVE,
            ]);
        }

        if (! $authenticated) {
            $decaySeconds = $protection['lock_minutes'] * 60;
            RateLimiter::hit($this->identityThrottleKey(), $decaySeconds);
            RateLimiter::hit($this->ipThrottleKey(), $decaySeconds);
            app(SecurityEventLogger::class)->record($this, 'login_failed', identifier: $identifier);

            throw ValidationException::withMessages([
                $this->errorKey() => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->identityThrottleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        $protection = app(TypedSystemSettingResolver::class)->loginProtection();
        $identityLimited = RateLimiter::tooManyAttempts($this->identityThrottleKey(), $protection['identity_attempts']);
        $ipLimited = RateLimiter::tooManyAttempts($this->ipThrottleKey(), $protection['ip_attempts']);

        if (! $identityLimited && ! $ipLimited) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $identityLimited ? RateLimiter::availableIn($this->identityThrottleKey()) : 0,
            $ipLimited ? RateLimiter::availableIn($this->ipThrottleKey()) : 0,
        );
        app(SecurityEventLogger::class)->record($this, 'login_locked', identifier: $this->loginIdentifier(), metadata: [
            'scope' => $identityLimited ? 'identity' : 'ip',
        ]);

        throw ValidationException::withMessages([
            $this->errorKey() => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function identityThrottleKey(): string
    {
        $identifier = $this->loginIdentifier();

        $phone = PhoneNumber::normalize($identifier);
        $identityKey = preg_match('/^0\d{9}$/', $phone) === 1 ? $phone : Str::lower($identifier);

        return 'login:identity:'.Str::transliterate($identityKey);
    }

    private function ipThrottleKey(): string
    {
        return 'login:ip:'.($this->ip() ?? 'unknown');
    }

    private function loginIdentifier(): string
    {
        return trim($this->string($this->filled('login') ? 'login' : 'email')->toString());
    }

    private function errorKey(): string
    {
        return $this->filled('login') ? 'login' : 'email';
    }
}
