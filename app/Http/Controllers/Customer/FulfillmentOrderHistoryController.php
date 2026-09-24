<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\FulfillmentOrderHistoryRequest;
use App\Models\FulfillmentOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FulfillmentOrderHistoryController extends Controller
{
    public function index(FulfillmentOrderHistoryRequest $request): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $filters = $request->validated();
        $q = trim((string) ($filters['q'] ?? ''));
        $orders = $customer->fulfillmentOrders()
            ->whereIn('fulfillment_type', [FulfillmentOrder::TYPE_PICKUP, FulfillmentOrder::TYPE_DELIVERY])
            ->withCount('items')
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested
                ->where('order_code', 'like', "%{$q}%")
                ->orWhere('customer_name', 'like', "%{$q}%")))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('fulfillment_type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment'] ?? null, fn (Builder $query, string $payment) => $query->where('payment_status', $payment))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('placed_at', '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('placed_at', '<=', CarbonImmutable::parse($to)->endOfDay()))
            ->latest('placed_at')->latest('id')->paginate(15)->withQueryString();

        return view('customer.fulfillment-orders.index', compact('orders', 'filters'));
    }

    public function show(Request $request, FulfillmentOrder $fulfillmentOrder): View
    {
        $customer = $request->user()->customer()->firstOrFail();
        $order = $customer->fulfillmentOrders()
            ->whereKey($fulfillmentOrder->id)
            ->whereIn('fulfillment_type', [FulfillmentOrder::TYPE_PICKUP, FulfillmentOrder::TYPE_DELIVERY])
            ->with(['items', 'voucher:id,code'])
            ->firstOrFail();

        return view('customer.fulfillment-orders.show', compact('order'));
    }
}
