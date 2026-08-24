<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterCustomerService
{
    /** @param array{name: string, email: string, phone: ?string, password: string} $attributes */
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $customerRole = Role::query()->where('code', 'customer')->firstOrFail();
            $user = User::query()->forceCreate([
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'role_id' => $customerRole->id,
                'status' => User::STATUS_ACTIVE,
                'last_login_at' => now(),
            ]);

            Customer::query()->forceCreate([
                'user_id' => $user->id,
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
                'email' => $attributes['email'],
            ]);

            return $user;
        });
    }
}
