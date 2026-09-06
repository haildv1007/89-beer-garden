<?php

namespace App\Services\CustomerOrder;

use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderItem;
use App\Models\Product;
use App\Services\Billing\BillCalculator;
use App\Services\Customer\CustomerIdentityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateFulfillmentOrderService
{
    public function __construct(
        private readonly BillCalculator $calculator,
        private readonly CustomerIdentityService $identity,
    ) {}

    /** @param array<string, mixed> $data */
    public function update(FulfillmentOrder $order, array $data): FulfillmentOrder
    {
        return DB::transaction(function () use ($order, $data): FulfillmentOrder {
            $locked = FulfillmentOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (
                $locked->status !== FulfillmentOrder::STATUS_PENDING ||
                $locked->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN
            ) {
                throw ValidationException::withMessages([
                    'order' => 'Chỉ đơn ngoài quán đang chờ xác nhận mới được chỉnh sửa.',
                ]);
            }
            if ($locked->payment_status === FulfillmentOrder::PAYMENT_PAID) {
                throw ValidationException::withMessages(['order' => 'Đơn đã thanh toán nên không thể chỉnh sửa.']);
            }

            $customer = $this->identity->resolve($data['customer_name'], $data['phone'], $data['email'] ?? null);
            $items = collect($data['items'])->values();
            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->with('category:id,status')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => 'Danh sách món không hợp lệ.']);
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
                    throw ValidationException::withMessages(['items' => 'Có món hiện không còn phục vụ.']);
                }
                $subtotal += $product->price * $quantity;
            }

            $discount = $locked->voucher_id
                ? $this->calculator->discount($locked->voucher()->lockForUpdate()->firstOrFail(), $subtotal)
                : 0;
            $baseTotal = $this->calculator->ensurePositiveTotal($subtotal, $discount);
            if ($baseTotal > PHP_INT_MAX - $locked->shipping_fee) {
                throw ValidationException::withMessages(['items' => 'Tổng tiền vượt giới hạn cho phép.']);
            }

            $locked
                ->forceFill([
                    'customer_id' => $customer->id,
                    'customer_name' => trim($data['customer_name']),
                    'phone' => trim($data['phone']),
                    'email' => filled($data['email'] ?? null) ? trim($data['email']) : null,
                    'delivery_address' => $locked->fulfillment_type === FulfillmentOrder::TYPE_DELIVERY
                            ? trim($data['delivery_address'])
                            : null,
                    'requested_for' => $data['requested_for'],
                    'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
                    'payment_option' => $data['payment_option'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $baseTotal + $locked->shipping_fee,
                ])
                ->save();

            FulfillmentOrderItem::query()->where('fulfillment_order_id', $locked->id)->delete();
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                FulfillmentOrderItem::query()->create([
                    'fulfillment_order_id' => $locked->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $product->price * $quantity,
                    'note' => $item['note'] ?? null,
                ]);
            }

            return $locked->fresh(['items']);
        });
    }
}
