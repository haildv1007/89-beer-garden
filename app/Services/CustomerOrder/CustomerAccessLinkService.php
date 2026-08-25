<?php

namespace App\Services\CustomerOrder;

use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class CustomerAccessLinkService
{
    public function __construct(private readonly CustomerOrderingCapability $capability) {}

    public function create(DiningSession $session): string
    {
        $session->loadMissing('table');
        if (! $this->capability->enabled()) {
            throw ValidationException::withMessages(['context' => __('customer_order.errors.disabled')]);
        }
        if ($session->status !== DiningSessionStatus::Active || ! $session->table?->is_active
            || $session->table->runtime_status !== RestaurantTableStatus::Occupied) {
            throw ValidationException::withMessages(['context' => __('customer_order.errors.context_invalid')]);
        }

        return URL::temporarySignedRoute(
            'customer.dining-context.bind',
            now()->addMinutes(max(1, (int) config('session.customer_order_access_minutes'))),
            ['diningSession' => $session->id],
        );
    }
}
