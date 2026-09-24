<?php

namespace App\Services\Order;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Billing\RefreshOpenBillService;
use App\Services\Kitchen\KitchenTicketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrderService
{
    public function __construct(
        private readonly RefreshOpenBillService $billRefresher,
        private readonly KitchenTicketService $kitchenTickets,
    ) {}

    public function cancel(DiningSession $session, Order $order, User $actor, string $reason): void
    {
        DB::transaction(function () use ($session, $order, $actor, $reason): void {
            $lockedSession = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $items = OrderItem::query()->where('order_id', $lockedOrder->id)->orderBy('id')->lockForUpdate()->get();
            $employee = Employee::query()->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->dining_session_id !== $lockedSession->id || $lockedSession->status !== DiningSessionStatus::Active) {
                throw ValidationException::withMessages(['order' => 'Lượt gọi món không thuộc phiên phục vụ đang hoạt động.']);
            }

            $remaining = $items->reject(fn (OrderItem $item) => $item->status === OrderItemStatus::Cancelled);
            if ($remaining->isEmpty() || $remaining->contains(fn (OrderItem $item) =>
                ! in_array($item->status, [OrderItemStatus::Waiting, OrderItemStatus::Preparing], true))) {
                throw ValidationException::withMessages(['order' => 'Lượt gọi đã hủy hết hoặc có món đã sẵn sàng/phục vụ.']);
            }
            if ($remaining->contains(fn (OrderItem $item) => $item->status === OrderItemStatus::Preparing)
                && ! $actor->can('order-item.cancel-preparing')) {
                abort(403);
            }

            foreach ($remaining as $item) {
                $item->forceFill([
                    'status' => OrderItemStatus::Cancelled,
                    'cancelled_by_employee_id' => $employee->id,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ])->save();
            }

            $this->billRefresher->refreshForLockedSession($lockedSession->id);
            $this->kitchenTickets->createCancellationForOrder($lockedOrder, $remaining, $employee, $reason);
        });
    }
}
