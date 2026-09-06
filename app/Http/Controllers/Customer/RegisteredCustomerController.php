<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RegisterCustomerRequest;
use App\Services\Customer\RegisterCustomerService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredCustomerController extends Controller
{
    public function create(TypedSystemSettingResolver $settings): View
    {
        return view('customer.registration.create', ['googleLoginEnabled' => $settings->googleOAuth() !== null]);
    }

    public function store(RegisterCustomerRequest $request, RegisterCustomerService $service): RedirectResponse
    {
        $user = $service->register($request->safe()->only(['name', 'email', 'phone', 'password']));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->flash('success', __('customer.registration.success'));

        return new RedirectResponse(route('customer.profile.show', $user->customer, false));
    }
}
