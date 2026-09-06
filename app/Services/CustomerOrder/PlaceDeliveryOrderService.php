<?php

namespace App\Services\CustomerOrder;

use App\Models\Customer;
use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Billing\BillCalculator;
use App\Services\BusinessCode\BusinessCodeGenerator;
use App\Services\Customer\CustomerIdentityService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceDeliveryOrderService
{
    public function __construct(
        private readonly CustomerOrderingCapability $capability,
        private readonly TypedSystemSettingResolver $settings,
        private readonly BillCalculator $calculator,
        private readonly CustomerIdentityService $identity,
        private readonly BusinessCodeGenerator $codes,
    ) {}

    /** @param list<array{product_id:int, quantity:int, note:?string}> $items */
    public function place(array $items, array $contact, ?User $actor, ?string $voucherCode): FulfillmentOrder
    {
        return DB::transaction(function () use ($items, $contact, $actor, $voucherCode): FulfillmentOrder {
            if (! $this->capability->enabled(lockForUpdate: true)) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.disabled')]);
            }
            $deliveryFee = $this->settings->deliveryFee(lockForUpdate: true);
            if ($deliveryFee === null) {
                throw ValidationException::withMessages([
                    'fulfillment_type' => __('customer_order.errors.delivery_unavailable'),
                ]);
            }

            $voucher =
                is_string($voucherCode) && $voucherCode !== ''
                    ? Voucher::query()->where('code', $voucherCode)->lockForUpdate()->first()
                    : null;
            if ($voucherCode && $voucher === null) {
                throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
            }

            $lockedActor = $actor === null ? null : User::query()->lockForUpdate()->find($actor->id);
            $customer =
                $lockedActor === null
                    ? null
                    : Customer::query()->where('user_id', $lockedActor->id)->lockForUpdate()->first();
            if ($lockedActor !== null && (! $lockedActor->isActive() || $customer === null)) {
                throw ValidationException::withMessages(['customer' => __('customer_order.errors.actor_invalid')]);
            }
            if ($lockedActor === null) {
                $customer = $this->identity->resolve(
                    $contact['customer_name'],
                    $contact['phone'],
                    $contact['email'] ?? null,
                );
            }

            $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->with('category:id,status')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                if (
                    $product->status !== Product::STATUS_ACTIVE ||
                    ! $product->is_available ||
                    $product->category?->status !== 'active' ||
                    $product->price > intdiv(PHP_INT_MAX, $quantity) ||
                    $product->price * $quantity > PHP_INT_MAX - $subtotal
                ) {
                    throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
                }
                $subtotal += $product->price * $quantity;
            }

            $discount = $voucher ? $this->calculator->discount($voucher, $subtotal) : 0;
            $this->calculator->ensurePositiveTotal($subtotal, $discount);
            if ($subtotal - $discount > PHP_INT_MAX - $deliveryFee) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }

            $order = FulfillmentOrder::query()->create([
                'order_code' => $this->codes->next(BusinessCodeGenerator::FULFILLMENT_ORDER),
                'customer_id' => $customer?->id,
                'voucher_id' => $voucher?->id,
                'fulfillment_type' => FulfillmentOrder::TYPE_DELIVERY,
                'payment_option' => $contact['payment_option'] ?? FulfillmentOrder::PAYMENT_ON_RECEIPT,
                'status' => FulfillmentOrder::STATUS_PENDING,
                'customer_name' => $contact['customer_name'],
                'phone' => $contact['phone'],
                'email' => $contact['email'] ?? null,
                'delivery_address' => trim($contact['delivery_address']),
                'requested_for' => $contact['requested_for'],
                'note' => $contact['note'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_fee' => $deliveryFee,
                'total_amount' => $subtotal - $discount + $deliveryFee,
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                FulfillmentOrderItem::query()->create([
                    'fulfillment_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'line_total' => $product->price * $item['quantity'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            return $order->load('items');
        });
    }
}
