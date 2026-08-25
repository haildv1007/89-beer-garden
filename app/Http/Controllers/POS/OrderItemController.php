<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CancelOrderItemRequest;
use App\Http\Requests\POS\MarkOrderItemServedRequest;
use App\Models\OrderItem;
use App\Services\OrderItem\CancelOrderItemService;
use App\Services\OrderItem\OrderItemTransitionService;
use Illuminate\Http\RedirectResponse;

class OrderItemController extends Controller
{
    public function markServed(MarkOrderItemServedRequest $request, OrderItem $orderItem, OrderItemTransitionService $service): RedirectResponse
    {
        $service->markServed($orderItem, $request->user());

        return back()->with('success', __('kitchen.served'));
    }

    public function cancelWaiting(CancelOrderItemRequest $request, OrderItem $orderItem, CancelOrderItemService $service): RedirectResponse
    {
        $service->cancelWaiting($orderItem, $request->user(), $request->validated('cancellation_reason'));

        return back()->with('success', __('kitchen.cancelled'));
    }

    public function cancelPreparing(CancelOrderItemRequest $request, OrderItem $orderItem, CancelOrderItemService $service): RedirectResponse
    {
        $service->cancelPreparing($orderItem, $request->user(), $request->validated('cancellation_reason'));

        return back()->with('success', __('kitchen.cancelled'));
    }
}
