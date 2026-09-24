<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\OrderHistoryRequest;
use App\Models\Bill;
use App\Models\FulfillmentOrder;
use App\Services\CustomerHistory\CustomerHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(OrderHistoryRequest $request, CustomerHistoryService $history): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $filters = $request->validated();
        $transactions = $history->paidTransactions($customer, $filters);
        $overview = $history->overview($customer);

        return view('customer.orders.history', compact('transactions', 'overview', 'filters'));
    }

    public function show(Request $request, Bill $bill, CustomerHistoryService $history): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $bill = $history->ownedPaidBill($customer, $bill);

        return view('customer.orders.show', compact('bill'));
    }

    public function showFulfillment(Request $request, FulfillmentOrder $fulfillmentOrder, CustomerHistoryService $history): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $order = $history->ownedFulfillmentOrder($customer, $fulfillmentOrder, true);

        return view('customer.fulfillment-orders.invoice', compact('order'));
    }
}
