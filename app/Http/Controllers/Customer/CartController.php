<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCartItemRequest;
use App\Http\Requests\Customer\SubmitCustomerOrderRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Models\DiningSession;
use App\Models\Product;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\CustomerOrder\CustomerDiningContextService;
use App\Services\Order\CreateOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, CustomerDiningContextService $context, CustomerCartService $cart): View
    {
        $session = $context->resolve($request);

        return view('customer.cart.index', ['diningSession' => $session, 'rows' => $cart->rows($request)]);
    }

    public function store(StoreCartItemRequest $request, CustomerDiningContextService $context, CustomerCartService $cart): RedirectResponse
    {
        $context->resolve($request);
        $data = $request->validated();
        $product = Product::with('category')->findOrFail((int) $data['product_id']);
        $cart->add($request, $product, (int) $data['quantity'], $data['note'] ?? null);

        return back()->with('success', __('customer_order.added'));
    }

    public function update(UpdateCartItemRequest $request, int $productId, CustomerDiningContextService $context, CustomerCartService $cart): RedirectResponse
    {
        $context->resolve($request);
        $data = $request->validated();
        $cart->update($request, $productId, (int) $data['quantity'], $data['note'] ?? null);

        return back()->with('success', __('customer_order.updated'));
    }

    public function destroy(Request $request, int $productId, CustomerDiningContextService $context, CustomerCartService $cart): RedirectResponse
    {
        $context->resolve($request);
        $cart->remove($request, $productId);

        return back()->with('success', __('customer_order.removed'));
    }

    public function clear(Request $request, CustomerDiningContextService $context, CustomerCartService $cart): RedirectResponse
    {
        $context->resolve($request);
        $cart->clear($request);

        return back()->with('success', __('customer_order.cleared'));
    }

    public function submit(SubmitCustomerOrderRequest $request, CustomerDiningContextService $context, CustomerCartService $cart, CreateOrderService $orders): RedirectResponse
    {
        $session = $context->resolve($request);
        $items = $cart->itemsForSubmit($request);
        $order = $orders->createForCustomer($session, $request->user(), $items, $request->validated('note'));
        $cart->clear($request);

        return redirect()->route('customer.cart.success')->with('customer_order_code', $order->order_code);
    }

    public function success(Request $request): View
    {
        return view('customer.cart.success', ['orderCode' => $request->session()->get('customer_order_code')]);
    }

    public function status(Request $request, CustomerDiningContextService $context): View
    {
        /** @var DiningSession $session */
        $session = $context->resolve($request);
        $session->load(['orders' => fn ($query) => $query->oldest('ordered_at'), 'orders.items']);

        return view('customer.cart.status', ['diningSession' => $session]);
    }
}
