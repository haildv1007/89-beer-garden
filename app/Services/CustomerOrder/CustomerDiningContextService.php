<?php

namespace App\Services\CustomerOrder;

use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerDiningContextService
{
    public const SESSION_KEY = 'customer_dining_context';

    public function __construct(private readonly CustomerOrderingCapability $capability) {}

    public function bind(Request $request, DiningSession $session): void
    {
        $this->assertCustomerActor($request);
        $this->assertCapability();
        $this->assertUsable($session->loadMissing('table'));

        $previousId = data_get($request->session()->get(self::SESSION_KEY), 'dining_session_id');
        if ((int) $previousId !== $session->id) {
            $request->session()->forget(CustomerCartService::SESSION_KEY);
        }
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, ['dining_session_id' => $session->id]);
    }

    public function resolve(Request $request): DiningSession
    {
        $this->assertCustomerActor($request);
        $this->assertCapability();
        $id = data_get($request->session()->get(self::SESSION_KEY), 'dining_session_id');
        if (! is_int($id) && ! ctype_digit((string) $id)) {
            return $this->invalidate($request);
        }

        $session = DiningSession::query()->with('table')->find((int) $id);
        if ($session === null) {
            return $this->invalidate($request);
        }

        try {
            $this->assertUsable($session);
        } catch (ValidationException) {
            return $this->invalidate($request);
        }

        return $session;
    }

    public function available(Request $request): bool
    {
        try {
            $this->resolve($request);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private function assertCustomerActor(Request $request): void
    {
        if ($request->user() !== null && ($request->user()->customer()->doesntExist() || $request->user()->hasActiveEmployee())) {
            throw ValidationException::withMessages(['context' => __('customer_order.errors.actor_invalid')]);
        }
    }

    private function assertCapability(): void
    {
        if (! $this->capability->enabled()) {
            throw ValidationException::withMessages(['context' => __('customer_order.errors.disabled')]);
        }
    }

    private function assertUsable(DiningSession $session): void
    {
        if ($session->status !== DiningSessionStatus::Active || ! $session->table?->is_active
            || $session->table->runtime_status !== RestaurantTableStatus::Occupied) {
            throw ValidationException::withMessages(['context' => __('customer_order.errors.context_invalid')]);
        }
    }

    private function invalidate(Request $request): never
    {
        $request->session()->forget([self::SESSION_KEY, CustomerCartService::SESSION_KEY]);
        throw ValidationException::withMessages(['context' => __('customer_order.errors.context_invalid')]);
    }
}
