<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\StoreOrderRequest;
use App\Http\Requests\POS\UpdateWaitingOrderItemRequest;
use App\Models\DiningSession;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Order\CreateOrderService;
use App\Services\Order\UpdateWaitingOrderItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(DiningSession $diningSession): View
    {
        $products = Product::query()->publicMenu()->where('is_available', true)
            ->with('category:id,name')->orderBy('name')->get(['id', 'category_id', 'name', 'price']);

        return view('pos.orders.create', compact('diningSession', 'products'));
    }

    public function store(StoreOrderRequest $request, DiningSession $diningSession, CreateOrderService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->create($diningSession, $request->user(), $data['items'], $data['note'] ?? null);

        return redirect()->route('pos.dining-sessions.show', $diningSession)->with('success', __('order.created'));
    }

    public function updateItem(UpdateWaitingOrderItemRequest $request, OrderItem $orderItem, UpdateWaitingOrderItemService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->update($orderItem, (int) $data['quantity'], $data['note'] ?? null);

        return back()->with('success', __('order.item_updated'));
    }
}
