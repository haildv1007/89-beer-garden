<?php

namespace App\Services\CustomerOrder;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerCartService
{
    public const SESSION_KEY = 'customer_order_cart';

    public function add(Request $request, Product $product, int $quantity, ?string $note, ?int $variantId = null): void
    {
        $this->assertProduct($product->loadMissing('category'));
        $variant = $this->resolveVariant($product, $variantId);
        $cart = $this->validatedCart($request);
        $key = $this->lineKey($product->id, $variant?->id);
        $existing = $cart[$key] ?? null;
        $newQuantity = $quantity + (is_array($existing) ? (int) ($existing['quantity'] ?? 0) : 0);
        if ($newQuantity > 1000) {
            throw ValidationException::withMessages(['quantity' => __('customer_order.errors.quantity')]);
        }
        $cart[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $newQuantity,
            'note' => $note ?? (is_array($existing) ? $existing['note'] ?? null : null),
        ];
        ksort($cart, SORT_NATURAL);
        $request->session()->put(self::SESSION_KEY, $cart);
    }

    /** @param list<array{product_id:int, quantity:int}> $items */
    public function addBatch(Request $request, array $items): void
    {
        $productIds = array_column($items, 'product_id');
        $products = Product::withTrashed()
            ->with(['category' => fn ($query) => $query->withTrashed(), 'variants'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages(['items' => __('recommendation.cart.set_invalid')]);
        }

        $cart = $this->validatedCart($request);
        $nextCart = $cart;
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (! $product instanceof Product || ! $this->isProductValid($product)) {
                throw ValidationException::withMessages(['items' => __('recommendation.cart.product_unavailable')]);
            }
            $variant = $this->resolveVariant($product, isset($item['variant_id']) ? (int) $item['variant_id'] : null, true);
            $key = $this->lineKey($product->id, $variant?->id);
            $existing = $cart[$key] ?? null;
            $newQuantity = (int) $item['quantity'] + (is_array($existing) ? (int) $existing['quantity'] : 0);
            if ($newQuantity > 1000) {
                throw ValidationException::withMessages(['items' => __('recommendation.cart.quantity_exceeded')]);
            }
            $nextCart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $newQuantity,
                'note' => is_array($existing) ? $existing['note'] ?? null : null,
            ];
        }

        ksort($nextCart, SORT_NATURAL);
        $request->session()->put(self::SESSION_KEY, $nextCart);
    }

    public function update(Request $request, string $lineKey, int $quantity, ?string $note): void
    {
        $cart = $this->validatedCart($request);
        if (! isset($cart[$lineKey])) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_item_missing')]);
        }
        $cart[$lineKey]['quantity'] = $quantity;
        $cart[$lineKey]['note'] = $note;
        $request->session()->put(self::SESSION_KEY, $cart);
    }

    public function remove(Request $request, string $lineKey): void
    {
        $cart = $this->validatedCart($request);
        unset($cart[$lineKey]);
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
        $cart = $this->validatedCart($request);
        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_empty')]);
        }
        $items = [];
        foreach ($cart as $item) {
            $items[] = [
                'product_id' => (int) $item['product_id'],
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => (int) $item['quantity'],
                'note' => $item['note'] ?? null,
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public function rows(Request $request): array
    {
        $cart = $this->validatedCart($request);
        $products = Product::withTrashed()
            ->with(['category' => fn ($query) => $query->withTrashed(), 'media', 'variants' => fn ($query) => $query->withTrashed()])
            ->whereIn('id', collect($cart)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function (array $item, int|string $lineKey) use ($products): array {
                $product = $products->get((int) $item['product_id']);
                $variant = $product?->variants->firstWhere('id', (int) ($item['variant_id'] ?? 0));
                $requiresVariant = $product?->variants->where('deleted_at', null)->isNotEmpty() ?? false;
                $available =
                    $product !== null &&
                    ! $product->trashed() &&
                    $product->status === Product::STATUS_ACTIVE &&
                    $product->is_available &&
                    $product->category !== null &&
                    ! $product->category->trashed() &&
                    $product->category->status === 'active';
                $available = $available && (! $requiresVariant || ($variant !== null && ! $variant->trashed() && $variant->is_available));
                $price = $variant?->price ?? ($requiresVariant ? null : $product?->price);
                $safeTotal = $price !== null && $price <= intdiv(PHP_INT_MAX, (int) $item['quantity']);

                return $item + [
                    'line_key' => (string) $lineKey,
                    'variant_id' => $variant?->id,
                    'variant_name' => $variant?->name,
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
        if (! $this->isProductValid($product)) {
            throw ValidationException::withMessages(['product' => __('customer_order.errors.product_invalid')]);
        }
    }

    private function isProductValid(Product $product): bool
    {
        return ! (
            $product->trashed() ||
            $product->status !== Product::STATUS_ACTIVE ||
            ! $product->is_available ||
            $product->category === null ||
            $product->category->trashed() ||
            $product->category->status !== 'active'
        );
    }

    private function resolveVariant(Product $product, ?int $variantId, bool $allowDefault = false): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants') ? $product->variants : $product->variants()->get();
        if ($variants->isEmpty()) {
            if ($variantId !== null) {
                throw ValidationException::withMessages(['variant_id' => 'Biến thể không hợp lệ.']);
            }

            return null;
        }
        $variant = $variantId === null && $allowDefault
            ? $variants->where('is_available', true)->sortBy('sort_order')->first()
            : $variants->firstWhere('id', $variantId);
        if (! $variant instanceof ProductVariant || ! $variant->is_available || $variant->trashed()) {
            throw ValidationException::withMessages(['variant_id' => 'Vui lòng chọn một biến thể đang được phục vụ.']);
        }

        return $variant;
    }

    private function lineKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? 0);
    }

    /** @return array<string, mixed> */
    private function validatedCart(Request $request): array
    {
        $cart = $request->session()->get(self::SESSION_KEY, []);
        if (! is_array($cart)) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
        }
        foreach ($cart as $key => $item) {
            if (
                ! is_array($item) ||
                ! isset($item['product_id'], $item['quantity']) ||
                ! in_array((string) $key, [(string) $item['product_id'], $this->lineKey((int) $item['product_id'], isset($item['variant_id']) ? (int) $item['variant_id'] : null)], true) ||
                filter_var($item['product_id'], FILTER_VALIDATE_INT) === false ||
                filter_var($item['quantity'], FILTER_VALIDATE_INT) === false ||
                (isset($item['variant_id']) && $item['variant_id'] !== null && filter_var($item['variant_id'], FILTER_VALIDATE_INT) === false) ||
                (int) $item['quantity'] < 1 ||
                (int) $item['quantity'] > 1000 ||
                (isset($item['note']) && (! is_string($item['note']) || mb_strlen($item['note']) > 2000))
            ) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }
        }

        return $cart;
    }
}
