@php
    $color = match ($status->value) {
        'waiting' => 'warning',
        'preparing' => 'primary',
        'ready' => 'success',
        'served' => 'dark',
        'cancelled' => 'secondary',
    };
@endphp
<span class="status-badge text-bg-{{ $color }}">{{ __('order.statuses.' . $status->value) }}</span>
