<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;

class EnsureCustomerProfileService
{
    /**
     * Ensure every customer account has one customer profile.
     *
     * This is intentionally called while the caller holds its transaction so a
     * concurrent sign-in cannot create two profiles for the same user.
     */
    public function ensure(User $user, string $name, ?string $email = null): Customer
    {
        $email = $email !== null ? mb_strtolower(trim($email)) : null;
        $customer = Customer::query()->where('user_id', $user->id)->lockForUpdate()->first();

        if ($customer === null && $email !== null && $email !== '') {
            $customer = Customer::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        if ($customer === null) {
            return Customer::query()->forceCreate([
                'user_id' => $user->id,
                'name' => trim($name),
                'phone' => $user->phone,
                'email' => $email,
            ]);
        }

        $customer->forceFill([
            'user_id' => $user->id,
            'email' => $email ?: $customer->email,
        ])->save();

        return $customer;
    }
}
