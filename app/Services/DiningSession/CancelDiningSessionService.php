<?php

namespace App\Services\DiningSession;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDiningSessionService
{
    public function cancel(DiningSession $session, Employee $employee, string $reason): DiningSession
    {
        return DB::transaction(function () use ($session, $employee, $reason): DiningSession {
            $locked = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status !== DiningSessionStatus::Active) {
                throw ValidationException::withMessages(['dining_session' => 'Chỉ phiên đang hoạt động mới được hủy.']);
            }
            $hasItems = $locked->orders()->whereHas('items', fn ($query) => $query
                ->where('status', '!=', OrderItemStatus::Cancelled->value))->lockForUpdate()->exists();
            if ($hasItems) {
                throw ValidationException::withMessages([
                    'dining_session' => 'Phiên còn món chưa hủy. Vui lòng hủy món hoặc cả lượt gọi trước.',
                ]);
            }
            $bill = $locked->bill()->lockForUpdate()->first();
            if ($bill) {
                throw ValidationException::withMessages([
                    'dining_session' => $bill->status === BillStatus::Paid
                        ? 'Phiên đã thanh toán nên không thể hủy.'
                        : 'Phiên đã mở hóa đơn nên không thể hủy trực tiếp.',
                ]);
            }

            $now = now();
            $locked->forceFill([
                'status' => DiningSessionStatus::Cancelled,
                'ended_at' => $now,
                'cancelled_at' => $now,
                'cancelled_by_employee_id' => $employee->id,
                'cancellation_reason' => trim($reason),
            ])->save();
            RestaurantTable::query()->whereKey($locked->table_id)->lockForUpdate()
                ->update(['runtime_status' => RestaurantTableStatus::Cleaning->value]);

            if ($locked->reservation_id) {
                Reservation::query()->whereKey($locked->reservation_id)->lockForUpdate()->update([
                    'status' => ReservationStatus::Cancelled->value,
                    'cancelled_at' => $now,
                    'cancelled_by_employee_id' => $employee->id,
                    'cancellation_reason' => trim($reason),
                ]);
            }

            return $locked->fresh();
        }, 3);
    }
}
