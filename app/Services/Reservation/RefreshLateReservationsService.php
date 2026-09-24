<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Carbon\CarbonImmutable;

class RefreshLateReservationsService
{
    public function __construct(private readonly TypedSystemSettingResolver $settings) {}

    public function refresh(): int
    {
        $cutoff = CarbonImmutable::now()->subMinutes($this->settings->lateCheckInMinutes());
        $ids = Reservation::query()
            ->where('status', ReservationStatus::Confirmed->value)
            ->whereDate('reservation_date', '<=', $cutoff->toDateString())
            ->get(['id', 'reservation_date', 'reservation_time'])
            ->filter(function (Reservation $reservation) use ($cutoff): bool {
                $scheduledAt = CarbonImmutable::parse(
                    $reservation->reservation_date->toDateString().' '.$reservation->reservation_time,
                    config('app.timezone'),
                );

                return $scheduledAt->lessThanOrEqualTo($cutoff);
            })
            ->pluck('id');

        return $ids->isEmpty()
            ? 0
            : Reservation::query()
                ->whereIn('id', $ids)
                ->where('status', ReservationStatus::Confirmed->value)
                ->update(['status' => ReservationStatus::Late->value, 'updated_at' => now()]);
    }
}
