<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkReservationNoShowService
{
    public function __construct(private readonly TypedSystemSettingResolver $settings) {}

    public function mark(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation): Reservation {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if (! in_array($lockedReservation->status, [ReservationStatus::Confirmed, ReservationStatus::Late], true)) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.confirmed_required')]);
            }

            $timeout = $this->settings->noShowTimeoutMinutes(lockForUpdate: true);
            if ($timeout === null) {
                throw ValidationException::withMessages([
                    'reservation' => __('reservation.errors.no_show_setting_missing'),
                ]);
            }

            $scheduledAt = CarbonImmutable::parse(
                $lockedReservation->reservation_date->toDateString().' '.$lockedReservation->reservation_time,
                config('app.timezone'),
            );
            $eligibleAt = $scheduledAt->addMinutes($timeout);

            if (now()->lessThan($eligibleAt)) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.no_show_too_early')]);
            }

            $lockedReservation
                ->forceFill([
                    'status' => ReservationStatus::NoShow,
                    'no_show_at' => now(),
                ])
                ->save();

            return $lockedReservation;
        });
    }
}
