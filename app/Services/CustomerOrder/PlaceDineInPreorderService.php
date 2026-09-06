<?php

namespace App\Services\CustomerOrder;

use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Billing\BillCalculator;
use App\Services\BusinessCode\BusinessCodeGenerator;
use App\Services\Reservation\CreateReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceDineInPreorderService
{
    public function __construct(
        private readonly CustomerOrderingCapability $capability,
        private readonly CreateReservationService $reservations,
        private readonly BillCalculator $calculator,
        private readonly BusinessCodeGenerator $codes,
    ) {}

    /** @param list<array{product_id:int, quantity:int, note:?string}> $items */
    public function place(array $reservationData, array $items, ?User $actor, ?string $voucherCode): Reservation
    {
        return DB::transaction(function () use ($reservationData, $items, $actor, $voucherCode): Reservation {
            if (! $this->capability->enabled(lockForUpdate: true)) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.disabled')]);
            }

            $reservation = $this->reservations->create($reservationData, $actor);
            $voucher =
                is_string($voucherCode) && $voucherCode !== ''
                    ? Voucher::query()->where('code', $voucherCode)->lockForUpdate()->first()
                    : null;
            if ($voucherCode && $voucher === null) {
                throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
            }

            $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->with('category:id,status')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($items === [] || $products->count() !== $productIds->count()) {
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
            $requestedFor = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $reservationData['reservation_date'].' '.$reservationData['reservation_time'],
                config('app.timezone'),
            );

            $order = FulfillmentOrder::query()->create([
                'order_code' => $this->codes->next(BusinessCodeGenerator::FULFILLMENT_ORDER),
                'customer_id' => $reservation->customer_id,
                'reservation_id' => $reservation->id,
                'voucher_id' => $voucher?->id,
                'fulfillment_type' => FulfillmentOrder::TYPE_DINE_IN,
                'status' => FulfillmentOrder::STATUS_PENDING,
                'customer_name' => $reservationData['name'],
                'phone' => $reservationData['phone'],
                'requested_for' => $requestedFor,
                'note' => $reservationData['note'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_fee' => 0,
                'total_amount' => $subtotal - $discount,
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

            return $reservation->load('preorder.items');
        });
    }
}
