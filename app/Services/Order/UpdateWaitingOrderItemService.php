<?php

namespace App\Services\Order;

use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Models\DiningSession;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateWaitingOrderItemService
{
    public function update(OrderItem $item, int $quantity, ?string $note): OrderItem
    {
        return DB::transaction(function () use ($item, $quantity, $note): OrderItem {
            $sessionId = $item->order()->value('dining_session_id');
            $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
            $lockedItem = OrderItem::query()->lockForUpdate()->findOrFail($item->id);

            if ($session->status !== DiningSessionStatus::Active || $lockedItem->status !== OrderItemStatus::Waiting) {
                throw ValidationException::withMessages(['order_item' => __('order.errors.waiting_required')]);
            }
            if ($lockedItem->unit_price > intdiv(PHP_INT_MAX, $quantity)) {
                throw ValidationException::withMessages(['quantity' => __('order.errors.quantity_overflow')]);
            }

            $lockedItem->forceFill([
                'quantity' => $quantity, 'line_total' => $lockedItem->unit_price * $quantity, 'note' => $note,
            ])->save();

            return $lockedItem;
        });
    }
}
