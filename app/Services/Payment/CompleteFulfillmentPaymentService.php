<?php

namespace App\Services\Payment;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteFulfillmentPaymentService
{
    public function complete(FulfillmentOrder $order, User $actor, string $method, ?int $cashReceived): FulfillmentOrder
    {
        return DB::transaction(function () use ($order, $actor, $method, $cashReceived): FulfillmentOrder {
            $locked = FulfillmentOrder::query()->lockForUpdate()->findOrFail($order->id);
            $employee = Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();
            if (
                $locked->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN ||
                $locked->status === FulfillmentOrder::STATUS_REJECTED
            ) {
                throw ValidationException::withMessages(['payment' => 'Đơn không còn hợp lệ để thanh toán.']);
            }
            if ($locked->payment_status === FulfillmentOrder::PAYMENT_PAID || $locked->paid_at !== null) {
                throw ValidationException::withMessages(['payment' => 'Đơn đã được thanh toán trước đó.']);
            }
            if ($locked->payment_option === FulfillmentOrder::PAYMENT_BANK_TRANSFER && $method !== 'bank_transfer') {
                throw ValidationException::withMessages([
                    'payment_method' => 'Đơn này đã chọn chuyển khoản ngân hàng.',
                ]);
            }
            $received = null;
            $change = null;
            if ($method === 'cash') {
                $received = $cashReceived ?? $locked->total_amount;
                if ($received < $locked->total_amount) {
                    throw ValidationException::withMessages([
                        'cash_received' => 'Tiền khách đưa chưa đủ tổng thanh toán.',
                    ]);
                }
                $change = $received - $locked->total_amount;
            }
            $locked
                ->forceFill([
                    'payment_status' => FulfillmentOrder::PAYMENT_PAID,
                    'payment_method' => $method,
                    'received_amount' => $received,
                    'change_amount' => $change,
                    'paid_at' => now(),
                    'paid_by_employee_id' => $employee->id,
                ])
                ->save();

            return $locked;
        });
    }
}
