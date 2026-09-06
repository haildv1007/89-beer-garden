<?php

namespace App\Services\Customer;

use App\Models\Role;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterCustomerService
{
    public function __construct(private readonly CustomerIdentityService $identity) {}

    /** @param array{name?: ?string, email?: ?string, phone: string, password: string} $attributes */
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $customerRole = Role::query()->where('code', 'customer')->firstOrFail();
            $phone = PhoneNumber::normalize($attributes['phone']);
            $customer = $this->identity->findByPhone($phone, true);
            if ($customer?->user_id !== null || User::query()->where('phone', $phone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'Số điện thoại này đã có tài khoản. Vui lòng đăng nhập thay vì đăng ký lại.',
                ]);
            }
            $name = trim((string) ($attributes['name'] ?? ''));
            $email = $attributes['email'] ?? null;
            $displayName = $name !== '' ? $name : ($customer?->name ?: 'Khách '.substr($phone, -4));
            $user = User::query()->forceCreate([
                'email' => $email,
                'phone' => $phone,
                'password' => $attributes['password'],
                'role_id' => $customerRole->id,
                'status' => User::STATUS_ACTIVE,
                'last_login_at' => now(),
            ]);

            if (! $customer) {
                $customer = $this->identity->resolve($displayName, $phone, $email);
            }
            $customer
                ->forceFill(
                    array_filter(
                        [
                            'user_id' => $user->id,
                            'phone' => $phone,
                            'name' => $name !== '' ? $name : null,
                            'email' => $email,
                        ],
                        fn ($value) => $value !== null,
                    ),
                )
                ->save();

            return $user;
        });
    }
}
