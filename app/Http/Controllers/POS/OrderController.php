<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CancelOrderRequest;
use App\Http\Requests\POS\StoreOrderRequest;
use App\Http\Requests\POS\UpdateWaitingOrderItemRequest;
use App\Models\DiningSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Order\CancelOrderService;
use App\Services\Order\CreateOrderService;
use App\Services\Order\UpdateWaitingOrderItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(DiningSession $diningSession): View
    {
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->with(['category:id,name', 'variants'])
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'price']);

        $adminContext = request()->routeIs('admin.*');

        return view('pos.orders.create', compact('diningSession', 'products', 'adminContext'));
    }

    public function store(
        StoreOrderRequest $request,
        DiningSession $diningSession,
        CreateOrderService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->create($diningSession, $request->user(), $data['items'], $data['note'] ?? null);

        $route = $request->routeIs('admin.*') ? 'admin.dining-sessions.show' : 'pos.dining-sessions.show';

        return redirect()->route($route, $diningSession)->with('success', __('order.created'));
    }

    public function updateItem(
        UpdateWaitingOrderItemRequest $request,
        OrderItem $orderItem,
        UpdateWaitingOrderItemService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->update($orderItem, $request->user(), (int) $data['quantity'], $data['note'] ?? null);

        return back()->with('success', __('order.item_updated'));
    }

    public function cancel(
        CancelOrderRequest $request,
        DiningSession $diningSession,
        Order $order,
        CancelOrderService $service,
    ): RedirectResponse {
        $service->cancel($diningSession, $order, $request->user(), $request->validated('cancellation_reason'));

        return back()->with('success', 'Đã hủy cả lượt gọi món và gửi phiếu hủy cho bếp.');
    }
}
