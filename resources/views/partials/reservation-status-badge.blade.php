@php
    $classes = match ($status) {
        \App\Enums\ReservationStatus::Pending => 'text-bg-warning',
        \App\Enums\ReservationStatus::Confirmed => 'text-bg-success',
        \App\Enums\ReservationStatus::CheckedIn => 'text-bg-primary',
        \App\Enums\ReservationStatus::Completed => 'text-bg-dark',
        \App\Enums\ReservationStatus::Rejected,
        \App\Enums\ReservationStatus::Cancelled,
        \App\Enums\ReservationStatus::NoShow => 'text-bg-secondary',
    };
@endphp
<span class="badge {{ $classes }}">{{ __('reservation.statuses.'.$status->value) }}</span>
