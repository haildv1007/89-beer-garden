@php
    $classes = match ($status) {
        \App\Enums\RestaurantTableStatus::Available => 'text-bg-success',
        \App\Enums\RestaurantTableStatus::Reserved => 'text-bg-info',
        \App\Enums\RestaurantTableStatus::Occupied => 'text-bg-warning',
        \App\Enums\RestaurantTableStatus::Cleaning => 'text-bg-secondary',
    };
@endphp
<span class="badge {{ $classes }}">{{ __('table.statuses.'.$status->value) }}</span>
