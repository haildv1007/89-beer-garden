<?php

namespace App\Services\DiningSession;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpenDiningSessionService
{
    public function open(RestaurantTable $table, User $actor, int $guestCount, ?int $customerId, ?string $note): DiningSession
    {
        return DB::transaction(function () use ($table, $actor, $guestCount, $customerId, $note): DiningSession {
            $employee = Employee::query()->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)->lockForUpdate()->firstOrFail();
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);

            $this->assertTableAvailable($lockedTable, $guestCount);

            $customer = $customerId === null ? null : Customer::query()->lockForUpdate()->findOrFail($customerId);
            $session = DiningSession::query()->forceCreate([
                'session_code' => 'DS-'.Str::ulid(), 'table_id' => $lockedTable->id,
                'customer_id' => $customer?->id, 'reservation_id' => null,
                'opened_by_employee_id' => $employee->id, 'status' => DiningSessionStatus::Active,
                'started_at' => now(), 'guest_count' => $guestCount, 'note' => $note,
            ]);
            $lockedTable->forceFill(['runtime_status' => RestaurantTableStatus::Occupied])->save();

            return $session;
        });
    }

    private function assertTableAvailable(RestaurantTable $table, int $guestCount): void
    {
        $hasActiveSession = DiningSession::query()->where('table_id', $table->id)
            ->where('status', DiningSessionStatus::Active->value)->lockForUpdate()->exists();

        if (! $table->is_active || $table->runtime_status !== RestaurantTableStatus::Available
            || $table->capacity < $guestCount || $hasActiveSession) {
            throw ValidationException::withMessages(['table' => __('dining_session.errors.table_unavailable')]);
        }
    }
}
