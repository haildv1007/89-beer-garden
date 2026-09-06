<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PlaceDeliveryOrderRequest;
use App\Http\Requests\Customer\PlacePickupOrderRequest;
use App\Models\FulfillmentOrder;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\CustomerOrder\PlaceDeliveryOrderService;
use App\Services\CustomerOrder\PlacePickupOrderService;
use App\Services\CustomerOrder\UniversalCartCheckoutService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartCheckoutController extends Controller
{
    public function show(Request $request, CustomerCartService $cart, UniversalCartCheckoutService $checkout): View
    {
        $rows = $cart->rows($request);
        if ($rows === []) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_empty')]);
        }
        $summary = $checkout->summary($request, $rows);
        if ($summary['fulfillment_type'] !== 'pickup') {
            throw ValidationException::withMessages([
                'fulfillment_type' => __('pickup_checkout.errors.pickup_required'),
            ]);
        }

        return view('customer.checkout.pickup', [
            'rows' => $rows,
            'summary' => $summary,
            'customer' => $request->user()?->customer,
        ]);
    }

    public function store(
        PlacePickupOrderRequest $request,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
        PlacePickupOrderService $orders,
    ): RedirectResponse {
        $rows = $cart->rows($request);
        if ($rows === [] || $checkout->summary($request, $rows)['fulfillment_type'] !== 'pickup') {
            throw ValidationException::withMessages([
                'fulfillment_type' => __('pickup_checkout.errors.pickup_required'),
            ]);
        }

        $order = $orders->place(
            $cart->itemsForSubmit($request),
            $request->validated(),
            $request->user(),
            $request->session()->get(UniversalCartCheckoutService::VOUCHER_KEY),
        );
        $cart->clear($request);

        if ($order->payment_option === FulfillmentOrder::PAYMENT_BANK_TRANSFER) {
            return redirect(
                URL::temporarySignedRoute('customer.cart.external-payment', now()->addDay(), [
                    'fulfillmentOrder' => $order,
                ]),
            );
        }

        return redirect()->route('customer.cart.checkout.success')->with('fulfillment_order_code', $order->order_code);
    }

    public function success(Request $request): View
    {
        return view('customer.checkout.pickup-success', [
            'orderCode' => $request->session()->get('fulfillment_order_code'),
        ]);
    }

    public function showDelivery(
        Request $request,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
    ): View {
        $rows = $cart->rows($request);
        if ($rows === []) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_empty')]);
        }
        $summary = $checkout->summary($request, $rows);
        if ($summary['fulfillment_type'] !== 'delivery') {
            throw ValidationException::withMessages([
                'fulfillment_type' => __('delivery_checkout.errors.delivery_required'),
            ]);
        }

        return view('customer.checkout.delivery', [
            'rows' => $rows,
            'summary' => $summary,
            'customer' => $request->user()?->customer,
        ]);
    }

    public function storeDelivery(
        PlaceDeliveryOrderRequest $request,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
        PlaceDeliveryOrderService $orders,
    ): RedirectResponse {
        $rows = $cart->rows($request);
        if ($rows === [] || $checkout->summary($request, $rows)['fulfillment_type'] !== 'delivery') {
            throw ValidationException::withMessages([
                'fulfillment_type' => __('delivery_checkout.errors.delivery_required'),
            ]);
        }

        $order = $orders->place(
            $cart->itemsForSubmit($request),
            $request->validated(),
            $request->user(),
            $request->session()->get(UniversalCartCheckoutService::VOUCHER_KEY),
        );
        $cart->clear($request);

        if ($order->payment_option === FulfillmentOrder::PAYMENT_BANK_TRANSFER) {
            return redirect(
                URL::temporarySignedRoute('customer.cart.external-payment', now()->addDay(), [
                    'fulfillmentOrder' => $order,
                ]),
            );
        }

        return redirect()
            ->route('customer.cart.delivery-checkout.success')
            ->with('fulfillment_order_code', $order->order_code);
    }

    public function deliverySuccess(Request $request): View
    {
        return view('customer.checkout.delivery-success', [
            'orderCode' => $request->session()->get('fulfillment_order_code'),
        ]);
    }

    public function payment(FulfillmentOrder $fulfillmentOrder, TypedSystemSettingResolver $settings): View
    {
        abort_unless(
            $fulfillmentOrder->payment_option === FulfillmentOrder::PAYMENT_BANK_TRANSFER &&
                $fulfillmentOrder->fulfillment_type !== FulfillmentOrder::TYPE_DINE_IN &&
                $fulfillmentOrder->payment_status === FulfillmentOrder::PAYMENT_UNPAID,
            404,
        );
        $bank = $settings->vietQr();
        $transferContent =
            $bank === null
                ? null
                : $bank['prefix'].substr(preg_replace('/[^A-Za-z0-9]/', '', $fulfillmentOrder->order_code), -12);

        return view('customer.checkout.bank-transfer', compact('fulfillmentOrder', 'bank', 'transferContent'));
    }

    public function reportPayment(Request $request, FulfillmentOrder $fulfillmentOrder): RedirectResponse
    {
        abort_unless(
            $fulfillmentOrder->payment_option === FulfillmentOrder::PAYMENT_BANK_TRANSFER &&
                $fulfillmentOrder->fulfillment_type !== FulfillmentOrder::TYPE_DINE_IN &&
                $fulfillmentOrder->payment_status === FulfillmentOrder::PAYMENT_UNPAID,
            404,
        );
        $fulfillmentOrder
            ->forceFill(['payment_reported_at' => $fulfillmentOrder->payment_reported_at ?? now()])
            ->save();
        $route =
            $fulfillmentOrder->fulfillment_type === FulfillmentOrder::TYPE_DELIVERY
                ? 'customer.cart.delivery-checkout.success'
                : 'customer.cart.checkout.success';

        return redirect()
            ->route($route)
            ->with('fulfillment_order_code', $fulfillmentOrder->order_code)
            ->with('payment_reported', true);
    }
}
