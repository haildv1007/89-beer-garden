<?php

namespace App\Services\Customer;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleCustomerLoginService
{
    public function __construct(private readonly EnsureCustomerProfileService $profiles) {}

    /** @param array{sub:string,email:string,email_verified?:bool,name?:string} $profile */
    public function login(array $profile): User
    {
        if (
            ($profile['email_verified'] ?? false) !== true ||
            ! filter_var($profile['email'] ?? null, FILTER_VALIDATE_EMAIL)
        ) {
            throw ValidationException::withMessages([
                'google' => 'Google chưa xác minh địa chỉ email của tài khoản này.',
            ]);
        }

        return DB::transaction(function () use ($profile): User {
            $email = mb_strtolower($profile['email']);
            $user =
                User::query()->where('google_id', $profile['sub'])->lockForUpdate()->first() ??
                User::query()->where('email', $email)->lockForUpdate()->first();
            $customerRole = Role::query()->where('code', 'customer')->firstOrFail();

            if ($user !== null && $user->role_id !== $customerRole->id) {
                throw ValidationException::withMessages([
                    'google' => 'Email này đang thuộc tài khoản nội bộ và không thể liên kết bằng Google.',
                ]);
            }

            if ($user === null) {
                $user = User::query()->forceCreate([
                    'email' => $email,
                    'phone' => null,
                    'google_id' => $profile['sub'],
                    'password' => Str::random(64),
                    'role_id' => $customerRole->id,
                    'status' => User::STATUS_ACTIVE,
                ]);
            } elseif ($user->google_id !== null && $user->google_id !== $profile['sub']) {
                throw ValidationException::withMessages([
                    'google' => 'Email này đã được liên kết với một tài khoản Google khác.',
                ]);
            } else {
                $user->forceFill(['google_id' => $profile['sub']])->save();
            }

            $this->profiles->ensure(
                $user,
                trim($profile['name'] ?? '') ?: strstr($email, '@', true),
                $email,
            );

            if (! $user->isActive()) {
                throw ValidationException::withMessages(['google' => 'Tài khoản hiện không hoạt động.']);
            }

            $user->forceFill(['last_login_at' => now()])->save();

            return $user;
        });
    }
}
