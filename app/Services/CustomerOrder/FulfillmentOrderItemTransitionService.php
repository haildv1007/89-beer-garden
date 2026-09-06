<?php

namespace App\Services\CustomerOrder;

use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentOrderItemTransitionService
{
    public function startPreparing(FulfillmentOrderItem $item, User $actor): FulfillmentOrderItem
    {
        return $this->transition($item, $actor, OrderItemStatus::Waiting, OrderItemStatus::Preparing);
    }

    public function markReady(FulfillmentOrderItem $item, User $actor): FulfillmentOrderItem
    {
        return $this->transition($item, $actor, OrderItemStatus::Preparing, OrderItemStatus::Ready);
    }

    private function transition(
        FulfillmentOrderItem $item,
        User $actor,
        OrderItemStatus $expected,
        OrderItemStatus $next,
    ): FulfillmentOrderItem {
        return DB::transaction(function () use ($item, $actor, $expected, $next): FulfillmentOrderItem {
            $orderId = FulfillmentOrderItem::query()->whereKey($item->id)->value('fulfillment_order_id');
            $order = FulfillmentOrder::query()->lockForUpdate()->findOrFail($orderId);
            $lockedItem = FulfillmentOrderItem::query()->lockForUpdate()->findOrFail($item->id);
            Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();
            if ($order->status !== FulfillmentOrder::STATUS_CONFIRMED || $lockedItem->status !== $expected) {
                throw ValidationException::withMessages(['order_item' => __('kitchen.errors.transition_invalid')]);
            }
            $lockedItem->forceFill(['status' => $next])->save();

            return $lockedItem;
        });
    }
}
