<?php

namespace App\Services\Order;

use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Models\DiningSession;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Kitchen\KitchenTicketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateWaitingOrderItemService
{
    public function __construct(private readonly KitchenTicketService $kitchenTickets) {}

    public function update(OrderItem $item, User $actor, int $quantity, ?string $note): OrderItem
    {
        return DB::transaction(function () use ($item, $actor, $quantity, $note): OrderItem {
            $sessionId = $item->order()->value('dining_session_id');
            $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
            $lockedItem = OrderItem::query()->lockForUpdate()->findOrFail($item->id);

            if ($session->status !== DiningSessionStatus::Active || $lockedItem->status !== OrderItemStatus::Waiting) {
                throw ValidationException::withMessages(['order_item' => __('order.errors.waiting_required')]);
            }
            if ($lockedItem->unit_price > intdiv(PHP_INT_MAX, $quantity)) {
                throw ValidationException::withMessages(['quantity' => __('order.errors.quantity_overflow')]);
            }

            $before = ['quantity' => $lockedItem->quantity, 'note' => $lockedItem->note];
            $lockedItem
                ->forceFill([
                    'quantity' => $quantity,
                    'line_total' => $lockedItem->unit_price * $quantity,
                    'note' => $note,
                ])
                ->save();

            if ($before['quantity'] !== $lockedItem->quantity || $before['note'] !== $lockedItem->note) {
                $this->kitchenTickets->createAdjustment($lockedItem, $actor, $before);
            }

            return $lockedItem;
        });
    }
}
