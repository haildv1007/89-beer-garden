<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateOwnCustomerProfileRequest;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\FulfillmentOrder;
use App\Models\User;
use App\Services\Customer\EnsureCustomerProfileService;
use App\Services\Customer\UpdateOwnCustomerProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, EnsureCustomerProfileService $profiles): View
    {
        $customer = $this->customerFor($request->user(), $profiles);
        $customer->loadCount('reservations');
        $paidBills = Bill::query()
            ->where('status', 'paid')
            ->whereHas('diningSession', fn ($query) => $query->where('customer_id', $customer->id))
            ->whereHas('successfulPayment', fn ($query) => $query->where('status', 'success'));
        $customer->setAttribute('paid_bills_count', (clone $paidBills)->count());
        $paidOutside = $customer->fulfillmentOrders()->where('payment_status', FulfillmentOrder::PAYMENT_PAID);
        $customer->setAttribute('paid_bills_count', $customer->paid_bills_count + (clone $paidOutside)->count());
        $customer->setAttribute('total_spending', (int) (clone $paidBills)->sum('total_amount') + (int) (clone $paidOutside)->sum('total_amount'));

        return view('customer.profile.show', compact('customer'));
    }

    public function edit(Request $request, EnsureCustomerProfileService $profiles): View
    {
        $customer = $this->customerFor($request->user(), $profiles);

        return view('customer.profile.edit', compact('customer'));
    }

    public function update(
        UpdateOwnCustomerProfileRequest $request,
        UpdateOwnCustomerProfileService $service,
        EnsureCustomerProfileService $profiles,
    ): RedirectResponse {
        $customer = $service->update(
            $this->customerFor($request->user(), $profiles),
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('customer.profile.show')->with('success', __('customer.profile.updated'));
    }

    private function customerFor(User $user, EnsureCustomerProfileService $profiles): Customer
    {
        return $user->customer()->first() ?? $profiles->ensure(
            $user,
            strstr((string) $user->email, '@', true) ?: 'Khách hàng',
            $user->email,
        );
    }
}
