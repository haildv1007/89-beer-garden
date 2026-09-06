<?php

namespace App\Services\Billing;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BillCalculator
{
    /**
     * @return array{orders: Collection<int, Order>, items: Collection<int, OrderItem>}
     */
    public function lockSessionOrdersAndItems(int $sessionId): array
    {
        $orders = Order::query()->where('dining_session_id', $sessionId)->orderBy('id')->lockForUpdate()->get();
        $items = $orders->isEmpty()
            ? collect()
            : OrderItem::query()->whereIn('order_id', $orders->pluck('id'))->orderBy('id')->lockForUpdate()->get();

        return ['orders' => $orders, 'items' => $items];
    }

    /** @param Collection<int, OrderItem> $items */
    public function subtotal(Collection $items): int
    {
        $billable = $items->reject(fn (OrderItem $item): bool => $item->status === OrderItemStatus::Cancelled);
        if ($billable->isEmpty()) {
            throw ValidationException::withMessages(['bill' => __('billing.errors.empty')]);
        }

        $subtotal = 0;
        foreach ($billable as $item) {
            if ($item->line_total > PHP_INT_MAX - $subtotal) {
                throw ValidationException::withMessages(['bill' => __('billing.errors.overflow')]);
            }
            $subtotal += $item->line_total;
        }

        if ($subtotal <= 0) {
            throw ValidationException::withMessages(['bill' => __('billing.errors.empty')]);
        }

        return $subtotal;
    }

    public function discount(Voucher $voucher, int $subtotal): int
    {
        $now = now();
        $valid =
            $voucher->status === Voucher::STATUS_ACTIVE &&
            $voucher->deleted_at === null &&
            $voucher->start_at->lessThanOrEqualTo($now) &&
            $voucher->end_at->greaterThanOrEqualTo($now) &&
            $subtotal >= $voucher->min_order_amount &&
            ($voucher->usage_limit === null || $voucher->used_count < $voucher->usage_limit);

        if (! $valid) {
            throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
        }

        if ($voucher->discount_type === Voucher::TYPE_FIXED) {
            $discount = $voucher->discount_value;
        } elseif ($voucher->discount_type === Voucher::TYPE_PERCENTAGE && $voucher->discount_value <= 100) {
            $discount =
                intdiv($subtotal, 100) * $voucher->discount_value +
                intdiv(($subtotal % 100) * $voucher->discount_value, 100);
        } else {
            throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
        }

        if ($voucher->max_discount_amount !== null) {
            $discount = min($discount, $voucher->max_discount_amount);
        }

        return min($discount, $subtotal);
    }

    public function ensurePositiveTotal(int $subtotal, int $discount): int
    {
        $total = $subtotal - $discount;
        if ($total <= 0) {
            throw ValidationException::withMessages(['voucher_code' => __('billing.errors.zero_total')]);
        }

        return $total;
    }
}
