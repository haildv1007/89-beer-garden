<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $this->errorKey() => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->errorKey() => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        $identifier = trim($this->string($this->filled('login') ? 'login' : 'email')->toString());

        $phone = PhoneNumber::normalize($identifier);
        $identityKey = preg_match('/^0\d{9}$/', $phone) === 1 ? $phone : Str::lower($identifier);

        return Str::transliterate($identityKey.'|'.$this->ip());
    }

    private function errorKey(): string
    {
        return $this->filled('login') ? 'login' : 'email';
    }
}
