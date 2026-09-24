<?php

namespace App\Services\DiningSession;

use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use App\Models\DiningSessionTableTransfer;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferDiningSessionTableService
{
    public function transfer(DiningSession $session, int $targetTableId, string $reason, Employee $employee): DiningSession
    {
        return DB::transaction(function () use ($session, $targetTableId, $reason, $employee): DiningSession {
            $locked = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status !== DiningSessionStatus::Active) {
                throw ValidationException::withMessages(['table_id' => 'Chỉ phiên đang phục vụ mới được đổi bàn.']);
            }
            if ($locked->table_id === $targetTableId) {
                throw ValidationException::withMessages(['table_id' => 'Bàn mới phải khác bàn hiện tại.']);
            }

            $tables = RestaurantTable::query()
                ->whereIn('id', [$locked->table_id, $targetTableId])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $tables->get($locked->table_id);
            $target = $tables->get($targetTableId);

            if (! $target || ! $target->is_active || $target->runtime_status !== RestaurantTableStatus::Available
                || $target->capacity < $locked->guest_count
                || DiningSession::query()->where('table_id', $targetTableId)->where('status', DiningSessionStatus::Active->value)->exists()) {
                throw ValidationException::withMessages(['table_id' => 'Bàn được chọn không còn trống hoặc không đủ chỗ.']);
            }

            $locked->forceFill(['table_id' => $target->id])->save();
            $source->forceFill(['runtime_status' => RestaurantTableStatus::Cleaning])->save();
            $target->forceFill(['runtime_status' => RestaurantTableStatus::Occupied])->save();

            if ($locked->reservation_id) {
                Reservation::query()->whereKey($locked->reservation_id)->lockForUpdate()->update(['table_id' => $target->id]);
            }

            DiningSessionTableTransfer::query()->create([
                'dining_session_id' => $locked->id,
                'from_table_id' => $source->id,
                'to_table_id' => $target->id,
                'transferred_by_employee_id' => $employee->id,
                'reason' => trim($reason),
                'transferred_at' => now(),
            ]);

            return $locked->fresh('table');
        }, 3);
    }
}
