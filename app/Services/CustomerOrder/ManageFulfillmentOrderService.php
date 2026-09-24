<?php

namespace App\Services\CustomerOrder;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\User;
use App\Services\Kitchen\KitchenTicketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageFulfillmentOrderService
{
    public function __construct(private readonly KitchenTicketService $kitchenTickets) {}

    public function confirm(FulfillmentOrder $order, User $actor): FulfillmentOrder
    {
        return $this->change($order, $actor, true, null);
    }

    public function reject(FulfillmentOrder $order, User $actor, string $reason): FulfillmentOrder
    {
        return $this->change($order, $actor, false, trim($reason));
    }

    private function change(FulfillmentOrder $order, User $actor, bool $confirm, ?string $reason): FulfillmentOrder
    {
        return DB::transaction(function () use ($order, $actor, $confirm, $reason): FulfillmentOrder {
            $locked = FulfillmentOrder::query()->lockForUpdate()->findOrFail($order->id);
            $employee = Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();
            if ($locked->status !== FulfillmentOrder::STATUS_PENDING) {
                throw ValidationException::withMessages(['order' => __('fulfillment_order.errors.not_pending')]);
            }
            if (! $confirm && $locked->payment_status === FulfillmentOrder::PAYMENT_PAID) {
                throw ValidationException::withMessages(['order' => 'Đơn đã thanh toán nên không thể từ chối.']);
            }

            $locked
                ->forceFill(
                    $confirm
                        ? [
                            'status' => FulfillmentOrder::STATUS_CONFIRMED,
                            'confirmed_by_employee_id' => $employee->id,
                            'confirmed_at' => now(),
                        ]
                        : [
                            'status' => FulfillmentOrder::STATUS_REJECTED,
                            'rejected_at' => now(),
                            'rejection_reason' => $reason,
                        ],
                )
                ->save();

            if ($confirm) {
                $this->kitchenTickets->createForFulfillmentOrder($locked, $employee);
            }

            return $locked;
        });
    }
}
