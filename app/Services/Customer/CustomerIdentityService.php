<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Support\PhoneNumber;

class CustomerIdentityService
{
    public function findByPhone(?string $phone, bool $lockForUpdate = false): ?Customer
    {
        $variants = PhoneNumber::variants($phone);
        if ($variants === []) {
            return null;
        }

        $query = Customer::query()->whereIn('phone', $variants)->orderByRaw('user_id is null')->orderBy('id');

        return ($lockForUpdate ? $query->lockForUpdate() : $query)->first();
    }

    public function resolve(string $name, ?string $phone, ?string $email = null): Customer
    {
        $normalized = PhoneNumber::normalize($phone);
        $customer = $this->findByPhone($normalized, true);
        if ($customer === null) {
            return Customer::query()->forceCreate(['name' => trim($name), 'phone' => $normalized, 'email' => $email]);
        }

        $customer
            ->forceFill(
                array_filter(
                    [
                        'name' => trim($name),
                        'phone' => $normalized,
                        'email' => $email,
                    ],
                    fn ($value) => $value !== null && $value !== '',
                ),
            )
            ->save();

        return $customer;
    }
}
