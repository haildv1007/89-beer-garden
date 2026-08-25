<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\OrderHistoryRequest;
use App\Models\DiningSession;
use App\Services\CustomerHistory\CustomerHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(OrderHistoryRequest $request, CustomerHistoryService $history): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $filters = $request->validated();
        $sessions = $history->sessions($customer, $filters);
        $overview = $history->overview($customer);

        return view('customer.orders.history', compact('sessions', 'overview', 'filters'));
    }

    public function show(Request $request, DiningSession $diningSession, CustomerHistoryService $history): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $diningSession = $history->ownedSession($customer, $diningSession);

        return view('customer.orders.show', compact('diningSession'));
    }
}
