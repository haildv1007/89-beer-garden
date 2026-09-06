<?php

namespace App\Services\CustomerOrder;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerCartService
{
    public const SESSION_KEY = 'customer_order_cart';

    public function add(Request $request, Product $product, int $quantity, ?string $note): void
    {
        $this->assertProduct($product->loadMissing('category'));
        $cart = $this->raw($request);
        $existing = $cart[(string) $product->id] ?? null;
        $newQuantity = $quantity + (is_array($existing) ? (int) ($existing['quantity'] ?? 0) : 0);
        if ($newQuantity > 1000) {
            throw ValidationException::withMessages(['quantity' => __('customer_order.errors.quantity')]);
        }
        $cart[(string) $product->id] = [
            'product_id' => $product->id,
            'quantity' => $newQuantity,
            'note' => $note ?? (is_array($existing) ? $existing['note'] ?? null : null),
        ];
        ksort($cart, SORT_NUMERIC);
        $request->session()->put(self::SESSION_KEY, $cart);
    }

    public function update(Request $request, int $productId, int $quantity, ?string $note): void
    {
        $cart = $this->raw($request);
        if (! isset($cart[(string) $productId])) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_item_missing')]);
        }
        $cart[(string) $productId] = ['product_id' => $productId, 'quantity' => $quantity, 'note' => $note];
        $request->session()->put(self::SESSION_KEY, $cart);
    }

    public function remove(Request $request, int $productId): void
    {
        $cart = $this->raw($request);
        unset($cart[(string) $productId]);
        $request->session()->put(self::SESSION_KEY, $cart);
    }

    public function clear(Request $request): void
    {
        $request
            ->session()
            ->forget([
                self::SESSION_KEY,
                UniversalCartCheckoutService::FULFILLMENT_KEY,
                UniversalCartCheckoutService::VOUCHER_KEY,
            ]);
    }

    /** @return list<array{product_id:int, quantity:int, note:?string}> */
    public function itemsForSubmit(Request $request): array
    {
        $cart = $this->raw($request);
        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_empty')]);
        }
        $items = [];
        foreach ($cart as $key => $item) {
            if (
                ! is_array($item) ||
                ! isset($item['product_id'], $item['quantity']) ||
                (string) $item['product_id'] !== (string) $key ||
                filter_var($item['product_id'], FILTER_VALIDATE_INT) === false ||
                filter_var($item['quantity'], FILTER_VALIDATE_INT) === false ||
                (int) $item['quantity'] < 1 ||
                (int) $item['quantity'] > 1000 ||
                (isset($item['note']) && (! is_string($item['note']) || mb_strlen($item['note']) > 2000))
            ) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }
            $items[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'note' => $item['note'] ?? null,
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public function rows(Request $request): array
    {
        $cart = $this->raw($request);
        foreach ($cart as $key => $item) {
            if (
                ! is_array($item) ||
                ! isset($item['product_id'], $item['quantity']) ||
                (string) $item['product_id'] !== (string) $key ||
                filter_var($item['product_id'], FILTER_VALIDATE_INT) === false ||
                filter_var($item['quantity'], FILTER_VALIDATE_INT) === false ||
                (int) $item['quantity'] < 1 ||
                (int) $item['quantity'] > 1000 ||
                (isset($item['note']) && (! is_string($item['note']) || mb_strlen($item['note']) > 2000))
            ) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }
        }
        $products = Product::withTrashed()
            ->with(['category' => fn ($query) => $query->withTrashed(), 'media'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function (array $item) use ($products): array {
                $product = $products->get((int) $item['product_id']);
                $available =
                    $product !== null &&
                    ! $product->trashed() &&
                    $product->status === Product::STATUS_ACTIVE &&
                    $product->is_available &&
                    $product->category !== null &&
                    ! $product->category->trashed() &&
                    $product->category->status === 'active';
                $price = $product?->price;
                $safeTotal = $price !== null && $price <= intdiv(PHP_INT_MAX, (int) $item['quantity']);

                return $item + [
                    'name' => $product?->name ?? __('customer_order.unavailable_product'),
                    'category' => $product?->category?->name,
                    'image_url' => $product?->primary_image_url,
                    'price' => $price,
                    'line_total' => $safeTotal ? $price * $item['quantity'] : null,
                    'available' => $available && $safeTotal,
                ];
            })
            ->values()
            ->all();
    }

    private function assertProduct(Product $product): void
    {
        if (
            $product->trashed() ||
            $product->status !== Product::STATUS_ACTIVE ||
            ! $product->is_available ||
            $product->category === null ||
            $product->category->trashed() ||
            $product->category->status !== 'active'
        ) {
            throw ValidationException::withMessages(['product' => __('customer_order.errors.product_invalid')]);
        }
    }

    /** @return array<string, mixed> */
    private function raw(Request $request): array
    {
        $cart = $request->session()->get(self::SESSION_KEY, []);

        return is_array($cart) ? $cart : [];
    }
}
