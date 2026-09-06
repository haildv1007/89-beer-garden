<?php

namespace App\Services\Reservation;

use App\Enums\EmployeeStatus;
use App\Enums\ReservationStatus;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmReservationService
{
    public function confirm(Reservation $reservation, User $actor): Reservation
    {
        return DB::transaction(function () use ($reservation, $actor): Reservation {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== ReservationStatus::Pending) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.pending_required')]);
            }

            $canAccommodate = RestaurantTable::query()
                ->where('is_active', true)
                ->where('capacity', '>=', $lockedReservation->party_size)
                ->lockForUpdate()
                ->get(['id'])
                ->isNotEmpty();

            if (! $canAccommodate) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.no_capacity')]);
            }

            $employee = Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReservation
                ->forceFill([
                    'status' => ReservationStatus::Confirmed,
                    'confirmed_by_employee_id' => $employee->id,
                    'confirmed_at' => now(),
                ])
                ->save();

            return $lockedReservation;
        });
    }
}
