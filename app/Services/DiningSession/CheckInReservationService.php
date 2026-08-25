<?php

namespace App\Services\DiningSession;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckInReservationService
{
    public function checkIn(Reservation $reservation, RestaurantTable $table, User $actor): DiningSession
    {
        return DB::transaction(function () use ($reservation, $table, $actor): DiningSession {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if ($lockedReservation->status !== ReservationStatus::Confirmed
                || DiningSession::query()->where('reservation_id', $lockedReservation->id)->exists()) {
                throw ValidationException::withMessages(['reservation' => __('dining_session.errors.confirmed_required')]);
            }

            $employee = Employee::query()->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)->lockForUpdate()->firstOrFail();
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            $hasActiveSession = DiningSession::query()->where('table_id', $lockedTable->id)
                ->where('status', DiningSessionStatus::Active->value)->lockForUpdate()->exists();

            if (! $lockedTable->is_active || $lockedTable->runtime_status !== RestaurantTableStatus::Available
                || $lockedTable->capacity < $lockedReservation->party_size || $hasActiveSession) {
                throw ValidationException::withMessages(['table' => __('dining_session.errors.table_unavailable')]);
            }

            $session = DiningSession::query()->forceCreate([
                'session_code' => 'DS-'.Str::ulid(), 'table_id' => $lockedTable->id,
                'customer_id' => $lockedReservation->customer_id, 'reservation_id' => $lockedReservation->id,
                'opened_by_employee_id' => $employee->id, 'status' => DiningSessionStatus::Active,
                'started_at' => now(), 'guest_count' => $lockedReservation->party_size,
            ]);
            $lockedReservation->forceFill([
                'table_id' => $lockedTable->id, 'status' => ReservationStatus::CheckedIn, 'checked_in_at' => now(),
            ])->save();
            $lockedTable->forceFill(['runtime_status' => RestaurantTableStatus::Occupied])->save();

            return $session;
        });
    }
}
