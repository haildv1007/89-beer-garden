<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateReservationService
{
    /** @param array{name: string, phone: string, reservation_date: string, reservation_time: string, party_size: int, note?: ?string} $attributes */
    public function create(array $attributes, ?User $user): Reservation
    {
        return DB::transaction(function () use ($attributes, $user): Reservation {
            if ($user === null) {
                $customer = Customer::query()->forceCreate([
                    'name' => $attributes['name'],
                    'phone' => $attributes['phone'],
                ]);
            } else {
                $customer = Customer::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $customer->forceFill([
                    'name' => $attributes['name'],
                    'phone' => $attributes['phone'],
                ])->save();
            }

            return Reservation::query()->forceCreate([
                'customer_id' => $customer->id,
                'reservation_code' => 'RSV-'.Str::ulid(),
                'reservation_date' => $attributes['reservation_date'],
                'reservation_time' => $attributes['reservation_time'],
                'party_size' => $attributes['party_size'],
                'status' => ReservationStatus::Pending,
                'note' => $attributes['note'] ?? null,
            ]);
        });
    }
}
