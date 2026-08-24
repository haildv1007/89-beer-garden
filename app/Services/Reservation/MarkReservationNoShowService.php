<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkReservationNoShowService
{
    public function mark(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation): Reservation {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== ReservationStatus::Confirmed) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.confirmed_required')]);
            }

            $setting = SystemSetting::query()
                ->where('key', 'no_show_timeout_minutes')
                ->lockForUpdate()
                ->first();

            if ($setting === null || $setting->type !== 'integer' || filter_var($setting->value, FILTER_VALIDATE_INT) === false || (int) $setting->value < 0) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.no_show_setting_missing')]);
            }

            $scheduledAt = CarbonImmutable::parse(
                $lockedReservation->reservation_date->toDateString().' '.$lockedReservation->reservation_time,
                config('app.timezone'),
            );
            $eligibleAt = $scheduledAt->addMinutes((int) $setting->value);

            if (now()->lessThan($eligibleAt)) {
                throw ValidationException::withMessages(['reservation' => __('reservation.errors.no_show_too_early')]);
            }

            $lockedReservation->forceFill([
                'status' => ReservationStatus::NoShow,
                'no_show_at' => now(),
            ])->save();

            return $lockedReservation;
        });
    }
}
