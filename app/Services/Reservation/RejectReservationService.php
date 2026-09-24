<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectReservationService
{
    public function reject(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation): Reservation {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== ReservationStatus::Pending) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.pending_required')]);
            }

            $lockedReservation->forceFill(['status' => ReservationStatus::Rejected])->save();

            return $lockedReservation;
        });
    }
}
