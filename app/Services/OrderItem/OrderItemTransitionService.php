<?php

namespace App\Services\OrderItem;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderItemTransitionService
{
    public function startPreparing(OrderItem $item, User $actor): OrderItem
    {
        return $this->transition($item, $actor, OrderItemStatus::Waiting, OrderItemStatus::Preparing);
    }

    public function markReady(OrderItem $item, User $actor): OrderItem
    {
        return $this->transition($item, $actor, OrderItemStatus::Preparing, OrderItemStatus::Ready);
    }

    public function markServed(OrderItem $item, User $actor): OrderItem
    {
        return $this->transition($item, $actor, OrderItemStatus::Ready, OrderItemStatus::Served);
    }

    private function transition(
        OrderItem $item,
        User $actor,
        OrderItemStatus $expected,
        OrderItemStatus $next,
    ): OrderItem {
        return DB::transaction(function () use ($item, $actor, $expected, $next): OrderItem {
            $orderId = OrderItem::query()->whereKey($item->id)->value('order_id');
            $sessionId = Order::query()->whereKey($orderId)->value('dining_session_id');
            $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            $lockedItem = OrderItem::query()->lockForUpdate()->findOrFail($item->id);
            Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $order->dining_session_id !== $session->id ||
                $lockedItem->order_id !== $order->id ||
                $session->status !== DiningSessionStatus::Active ||
                $lockedItem->status !== $expected
            ) {
                throw ValidationException::withMessages(['order_item' => __('kitchen.errors.transition_invalid')]);
            }

            $lockedItem->forceFill(['status' => $next])->save();

            return $lockedItem;
        });
    }
}
