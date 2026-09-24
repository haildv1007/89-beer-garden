<?php

namespace App\Services\Reservation;

use App\Enums\EmployeeStatus;
use App\Enums\ReservationStatus;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelReservationService
{
    public function cancel(Reservation $reservation, User $actor, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $actor, $reason): Reservation {
            $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if (! in_array(
                $locked->status,
                [ReservationStatus::Pending, ReservationStatus::Confirmed, ReservationStatus::Late],
                true,
            )) {
                throw ValidationException::withMessages([
                    'reservation' => 'Chỉ yêu cầu chưa check-in mới có thể hủy.',
                ]);
            }

            $employee = Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();
            $preorder = FulfillmentOrder::query()
                ->where('reservation_id', $locked->id)
                ->lockForUpdate()
                ->first();
            if ($preorder?->payment_status === FulfillmentOrder::PAYMENT_PAID) {
                throw ValidationException::withMessages([
                    'reservation' => 'Yêu cầu có món đặt trước đã thanh toán. Hãy xử lý hoàn tiền trước khi hủy.',
                ]);
            }

            $reason = trim($reason);
            $locked->forceFill([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by_employee_id' => $employee->id,
                'cancellation_reason' => $reason,
            ])->save();

            if ($preorder !== null && $preorder->status === FulfillmentOrder::STATUS_PENDING) {
                $preorder->forceFill([
                    'status' => FulfillmentOrder::STATUS_REJECTED,
                    'rejected_at' => now(),
                    'rejection_reason' => $reason,
                ])->save();
            }

            return $locked;
        });
    }
}
