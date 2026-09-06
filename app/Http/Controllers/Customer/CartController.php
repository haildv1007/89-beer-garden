<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ApplyCheckoutVoucherRequest;
use App\Http\Requests\Customer\SelectCartFulfillmentRequest;
use App\Http\Requests\Customer\StoreCartItemRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Models\Product;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\CustomerOrder\UniversalCartCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, CustomerCartService $cart, UniversalCartCheckoutService $checkout): View
    {
        $rows = $cart->rows($request);

        return view('customer.cart.index', [
            'rows' => $rows,
            'summary' => $rows === [] ? null : $checkout->summary($request, $rows),
        ]);
    }

    public function mini(Request $request, CustomerCartService $cart): JsonResponse
    {
        return response()->json($this->miniCartPayload($request, $cart));
    }

    public function store(StoreCartItemRequest $request, CustomerCartService $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $product = Product::with('category')->findOrFail((int) $data['product_id']);
        $cart->add($request, $product, (int) $data['quantity'], $data['note'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(
                $this->miniCartPayload($request, $cart) + [
                    'message' => __('customer_order.added'),
                ],
            );
        }

        return back();
    }

    public function update(
        UpdateCartItemRequest $request,
        int $productId,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
    ): RedirectResponse|JsonResponse {
        $data = $request->validated();
        $cart->update($request, $productId, (int) $data['quantity'], $data['note'] ?? null);

        if ($request->expectsJson()) {
            $rows = $cart->rows($request);
            $row = collect($rows)->firstWhere('product_id', $productId);

            return response()->json([
                'message' => __('customer_order.updated'),
                'quantity' => $row['quantity'] ?? null,
                'line_total' => $row['line_total'] ?? null,
                'cart_count' => collect($rows)->sum('quantity'),
                'summary' => $checkout->summary($request, $rows),
                'mini_cart' => $this->miniCartPayload($request, $cart),
            ]);
        }

        return back()->with('success', __('customer_order.updated'));
    }

    public function destroy(Request $request, int $productId, CustomerCartService $cart): RedirectResponse|JsonResponse
    {
        $cart->remove($request, $productId);

        if ($request->expectsJson()) {
            return response()->json(
                $this->miniCartPayload($request, $cart) + [
                    'message' => __('customer_order.removed'),
                ],
            );
        }

        return back()->with('success', __('customer_order.removed'));
    }

    public function clear(Request $request, CustomerCartService $cart): RedirectResponse
    {
        $cart->clear($request);

        return back()->with('success', __('customer_order.cleared'));
    }

    public function selectFulfillment(
        SelectCartFulfillmentRequest $request,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
    ): RedirectResponse|JsonResponse {
        $type = $request->validated('fulfillment_type');
        $checkout->selectFulfillment($request, $type);

        if ($request->expectsJson()) {
            $rows = $cart->rows($request);

            return response()->json([
                'message' => __('customer_order.fulfillment_selected'),
                'fulfillment_type' => $type,
                'next_message' => __('customer_order.fulfillment_details_next', [
                    'type' => __('customer_order.fulfillment.'.$type),
                ]),
                'summary' => $checkout->summary($request, $rows),
            ]);
        }

        return back()->with('success', __('customer_order.fulfillment_selected'));
    }

    public function applyVoucher(
        ApplyCheckoutVoucherRequest $request,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
    ): RedirectResponse {
        $checkout->applyVoucher($request, $cart->rows($request), $request->validated('voucher_code'));

        return back()->with('success', __('checkout.applied'));
    }

    public function removeVoucher(Request $request, UniversalCartCheckoutService $checkout): RedirectResponse
    {
        $checkout->removeVoucher($request);

        return back()->with('success', __('checkout.removed'));
    }

    /** @return array{items:list<array<string, mixed>>, cart_count:int, subtotal:int} */
    private function miniCartPayload(Request $request, CustomerCartService $cart): array
    {
        $rows = $cart->rows($request);

        return [
            'items' => collect($rows)
                ->map(
                    fn (array $row): array => [
                        'product_id' => $row['product_id'],
                        'name' => $row['name'],
                        'image_url' => $row['image_url'],
                        'quantity' => $row['quantity'],
                        'note' => $row['note'],
                        'price' => $row['price'],
                        'line_total' => $row['line_total'],
                        'available' => $row['available'],
                    ],
                )
                ->values()
                ->all(),
            'cart_count' => (int) collect($rows)->sum('quantity'),
            'subtotal' => (int) collect($rows)->sum(fn (array $row): int => (int) ($row['line_total'] ?? 0)),
        ];
    }
}
