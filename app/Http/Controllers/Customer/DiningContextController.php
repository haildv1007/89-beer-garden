<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningSession;
use App\Services\CustomerOrder\CustomerDiningContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiningContextController extends Controller
{
    public function bind(Request $request, DiningSession $diningSession, CustomerDiningContextService $service): RedirectResponse
    {
        $service->bind($request, $diningSession);

        return redirect()->route('customer.menu.index')->with('success', __('customer_order.context_bound'));
    }
}
