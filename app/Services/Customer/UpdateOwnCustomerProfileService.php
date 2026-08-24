<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOwnCustomerProfileService
{
    /** @param array{name: string, email: string, phone: ?string} $attributes */
    public function update(Customer $customer, User $user, array $attributes): Customer
    {
        return DB::transaction(function () use ($customer, $user, $attributes): Customer {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedCustomer = Customer::query()
                ->whereKey($customer->id)
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['email' => $attributes['email']])->save();
            $lockedCustomer->forceFill([
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
                'email' => $attributes['email'],
            ])->save();

            return $lockedCustomer;
        });
    }
}
