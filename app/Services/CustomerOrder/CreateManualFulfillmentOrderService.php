<?php

namespace App\Services\CustomerOrder;

use App\Models\FulfillmentOrder;
use App\Models\Product;
use App\Services\BusinessCode\BusinessCodeGenerator;
use App\Services\Customer\CustomerIdentityService;
use App\Services\Menu\ProductVariantSelectionService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateManualFulfillmentOrderService
{
    public function __construct(
        private readonly CustomerIdentityService $identity,
        private readonly TypedSystemSettingResolver $settings,
        private readonly BusinessCodeGenerator $codes,
        private readonly ProductVariantSelectionService $variants,
    ) {}

    public function create(array $data): FulfillmentOrder
    {
        return DB::transaction(function () use ($data): FulfillmentOrder {
            $ids = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->with(['category:id,status', 'variants'])
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($products->count() !== $ids->unique()->count()) {
                throw ValidationException::withMessages(['items' => 'Danh sách món không hợp lệ.']);
            }
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $variant = $this->variants->resolve($product, $item['variant_id'] ?? null);
                $unitPrice = $this->variants->price($product, $variant);
                if (
                    $product->status !== Product::STATUS_ACTIVE ||
                    ! $product->is_available ||
                    $product->category?->status !== 'active'
                ) {
                    throw ValidationException::withMessages(['items' => 'Có món hiện không còn phục vụ.']);
                }
                $subtotal += $unitPrice * $quantity;
            }
            $delivery = $data['fulfillment_type'] === FulfillmentOrder::TYPE_DELIVERY;
            $shippingFee = $delivery ? $this->settings->deliveryFee(lockForUpdate: true) ?? 0 : 0;
            $customer = $this->identity->resolve($data['customer_name'], $data['phone']);
            $order = FulfillmentOrder::query()->create([
                'order_code' => $this->codes->next(BusinessCodeGenerator::FULFILLMENT_ORDER),
                'customer_id' => $customer->id,
                'fulfillment_type' => $data['fulfillment_type'],
                'payment_option' => $data['payment_option'],
                'status' => FulfillmentOrder::STATUS_PENDING,
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'delivery_address' => $delivery ? $data['delivery_address'] : null,
                'requested_for' => $data['requested_for'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'shipping_fee' => $shippingFee,
                'total_amount' => $subtotal + $shippingFee,
                'placed_at' => now(),
            ]);
            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $variant = $this->variants->resolve($product, $item['variant_id'] ?? null);
                $unitPrice = $this->variants->price($product, $variant);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_variant_id' => $variant?->id,
                    'variant_name' => $variant?->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $item['quantity'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            return $order;
        });
    }
}
