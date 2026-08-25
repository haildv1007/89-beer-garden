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

class CancelOrderItemService
{
    public function cancelWaiting(OrderItem $item, User $actor, string $reason): OrderItem
    {
        return $this->cancel($item, $actor, $reason, OrderItemStatus::Waiting);
    }

    public function cancelPreparing(OrderItem $item, User $actor, string $reason): OrderItem
    {
        return $this->cancel($item, $actor, $reason, OrderItemStatus::Preparing);
    }

    private function cancel(OrderItem $item, User $actor, string $reason, OrderItemStatus $expected): OrderItem
    {
        return DB::transaction(function () use ($item, $actor, $reason, $expected): OrderItem {
            $orderId = OrderItem::query()->whereKey($item->id)->value('order_id');
            $sessionId = Order::query()->whereKey($orderId)->value('dining_session_id');
            $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            $lockedItem = OrderItem::query()->lockForUpdate()->findOrFail($item->id);
            $employee = Employee::query()->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)->lockForUpdate()->firstOrFail();

            if ($order->dining_session_id !== $session->id || $lockedItem->order_id !== $order->id
                || $session->status !== DiningSessionStatus::Active || $lockedItem->status !== $expected) {
                throw ValidationException::withMessages(['order_item' => __('kitchen.errors.cancellation_invalid')]);
            }

            $lockedItem->forceFill([
                'status' => OrderItemStatus::Cancelled,
                'cancelled_by_employee_id' => $employee->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            return $lockedItem;
        });
    }
}
