<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CreateCustomerAccessLinkRequest;
use App\Models\DiningSession;
use App\Services\CustomerOrder\CustomerAccessLinkService;
use Illuminate\Http\RedirectResponse;

class CustomerAccessLinkController extends Controller
{
    public function store(CreateCustomerAccessLinkRequest $request, DiningSession $diningSession, CustomerAccessLinkService $service): RedirectResponse
    {
        return back()->with('customer_access_url', $service->create($diningSession));
    }
}
