<?php

namespace App\Services\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Services\Billing\BillCalculator;
use App\Services\BusinessCode\BusinessCodeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateReservationService
{
    public function __construct(
        private readonly BillCalculator $calculator,
        private readonly BusinessCodeGenerator $codes,
    ) {}

    /** @param array<string, mixed> $data */
    public function update(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data): Reservation {
            $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if (! in_array($locked->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
                throw ValidationException::withMessages([
                    'reservation' => 'Đặt bàn đã check-in hoặc kết thúc nên không thể sửa tại đây.',
                ]);
            }

            $name = trim((string) $data['name']);
            $phone = trim((string) $data['phone']);
            $currentCustomer = Customer::query()->lockForUpdate()->findOrFail($locked->customer_id);
            $matchingCustomer = Customer::query()
                ->where('phone', $phone)
                ->whereKeyNot($currentCustomer->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            $customer = $matchingCustomer ?? $currentCustomer;
            $customer->forceFill(['name' => $name, 'phone' => $phone])->save();

            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => ! empty($item['product_id']))
                ->values();
            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->values();
            $products = Product::query()
                ->with('category:id,status')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($products->count() !== $productIds->unique()->count()) {
                throw ValidationException::withMessages(['items' => 'Danh sách món không hợp lệ.']);
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                if (
                    $product->status !== Product::STATUS_ACTIVE ||
                    ! $product->is_available ||
                    $product->category?->status !== 'active'
                ) {
                    throw ValidationException::withMessages(['items' => 'Có món hiện không còn phục vụ.']);
                }
                $subtotal += $product->price * $quantity;
            }

            $locked
                ->fill([
                    'reservation_date' => $data['reservation_date'],
                    'reservation_time' => $data['reservation_time'],
                    'party_size' => $data['party_size'],
                    'note' => $data['note'] ?? null,
                ])
                ->forceFill(['customer_id' => $customer->id])
                ->save();

            $preorder = FulfillmentOrder::query()->where('reservation_id', $locked->id)->lockForUpdate()->first();
            if ($preorder && $preorder->status !== FulfillmentOrder::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'items' => 'Món đã được gửi xử lý nên không thể sửa tại đặt bàn.',
                ]);
            }
            if ($items->isEmpty()) {
                $preorder?->delete();

                return $locked->fresh();
            }

            $arrival = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $data['reservation_date'].' '.$data['reservation_time'],
                config('app.timezone'),
            );
            $preorder ??= FulfillmentOrder::query()->create([
                'order_code' => $this->codes->next(BusinessCodeGenerator::FULFILLMENT_ORDER),
                'customer_id' => $locked->customer_id,
                'reservation_id' => $locked->id,
                'fulfillment_type' => FulfillmentOrder::TYPE_DINE_IN,
                'status' => FulfillmentOrder::STATUS_PENDING,
                'customer_name' => $locked->customer->name,
                'phone' => $locked->customer->phone,
                'email' => $locked->customer->email,
                'requested_for' => $arrival,
                'subtotal' => 0,
                'discount_amount' => 0,
                'shipping_fee' => 0,
                'total_amount' => 0,
                'placed_at' => now(),
            ]);
            $discount = $preorder->voucher_id
                ? $this->calculator->discount($preorder->voucher()->lockForUpdate()->firstOrFail(), $subtotal)
                : 0;
            $preorder
                ->forceFill([
                    'customer_id' => $customer->id,
                    'customer_name' => $name,
                    'phone' => $phone,
                    'requested_for' => $arrival,
                    'note' => $data['note'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $this->calculator->ensurePositiveTotal($subtotal, $discount),
                ])
                ->save();
            FulfillmentOrderItem::query()->where('fulfillment_order_id', $preorder->id)->delete();
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                FulfillmentOrderItem::query()->create([
                    'fulfillment_order_id' => $preorder->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $product->price,
                    'line_total' => $product->price * (int) $item['quantity'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            return $locked->fresh();
        });
    }
}
