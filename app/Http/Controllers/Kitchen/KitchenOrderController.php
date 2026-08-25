<?php

namespace App\Http\Controllers\Kitchen;

use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kitchen\KitchenQueueRequest;
use App\Http\Requests\Kitchen\ProcessOrderItemRequest;
use App\Models\OrderItem;
use App\Services\OrderItem\OrderItemTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KitchenOrderController extends Controller
{
    public function index(KitchenQueueRequest $request): View
    {
        $items = OrderItem::query()
            ->whereIn('status', [OrderItemStatus::Waiting->value, OrderItemStatus::Preparing->value, OrderItemStatus::Ready->value])
            ->whereHas('order.diningSession', fn ($query) => $query->where('status', DiningSessionStatus::Active->value))
            ->with(['order:id,order_code,dining_session_id,ordered_at',
                'order.diningSession:id,session_code,table_id', 'order.diningSession.table:id,code,name'])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->orderByRaw("FIELD(order_items.status, 'waiting', 'preparing', 'ready')")
            ->orderBy('orders.ordered_at')->orderBy('order_items.id')
            ->select('order_items.*')->get()->groupBy(fn (OrderItem $item) => $item->status->value);

        return view('kitchen.queue', compact('items'));
    }

    public function startPreparing(ProcessOrderItemRequest $request, OrderItem $orderItem, OrderItemTransitionService $service): RedirectResponse
    {
        $service->startPreparing($orderItem, $request->user());

        return back()->with('success', __('kitchen.started'));
    }

    public function markReady(ProcessOrderItemRequest $request, OrderItem $orderItem, OrderItemTransitionService $service): RedirectResponse
    {
        $service->markReady($orderItem, $request->user());

        return back()->with('success', __('kitchen.ready'));
    }
}
