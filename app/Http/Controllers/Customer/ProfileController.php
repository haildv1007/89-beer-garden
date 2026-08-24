<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateOwnCustomerProfileRequest;
use App\Models\Customer;
use App\Services\Customer\UpdateOwnCustomerProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Customer $customer): View
    {
        Gate::authorize('viewOwn', $customer);
        $customer->loadCount(['reservations', 'diningSessions', 'orders']);

        return view('customer.profile.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('updateOwn', $customer);

        return view('customer.profile.edit', compact('customer'));
    }

    public function update(UpdateOwnCustomerProfileRequest $request, Customer $customer, UpdateOwnCustomerProfileService $service): RedirectResponse
    {
        Gate::authorize('updateOwn', $customer);
        $customer = $service->update($customer, $request->user(), $request->validated());

        return redirect()->route('customer.profile.show', $customer)->with('success', __('customer.profile.updated'));
    }
}
