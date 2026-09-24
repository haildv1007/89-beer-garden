<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateOwnCustomerProfileService
{
    /** @param array{name: string, email: string, phone: ?string} $attributes */
    public function update(Customer $customer, User $user, array $attributes): Customer
    {
        $newAvatar = isset($attributes['avatar'])
            ? $attributes['avatar']->storePublicly('customer-avatars', 'public')
            : null;
        $oldAvatar = $customer->avatar_path;
        try {
            $updated = DB::transaction(function () use ($customer, $user, $attributes, $newAvatar): Customer {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $lockedCustomer = Customer::query()
                    ->whereKey($customer->id)
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedUser->forceFill(['email' => $attributes['email']])->save();
                $lockedCustomer
                    ->forceFill([
                        'name' => $attributes['name'],
                        'phone' => $attributes['phone'] ?? null,
                        'email' => $attributes['email'],
                        'avatar_path' => $newAvatar ?? $lockedCustomer->avatar_path,
                    ])
                    ->save();

                return $lockedCustomer;
            });
        } catch (Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $exception;
        }
        if ($newAvatar && $oldAvatar && $oldAvatar !== $newAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $updated;
    }
}
