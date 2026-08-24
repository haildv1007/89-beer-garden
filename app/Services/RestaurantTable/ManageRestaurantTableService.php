<?php

namespace App\Services\RestaurantTable;

use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageRestaurantTableService
{
    /** @param array{code: string, name: string, capacity: int, location: ?string, is_active: bool} $attributes */
    public function update(RestaurantTable $table, array $attributes): RestaurantTable
    {
        return DB::transaction(function () use ($table, $attributes): RestaurantTable {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);

            if ($lockedTable->is_active && ! $attributes['is_active']) {
                $this->ensureMayBeRemovedFromService($lockedTable);
            }

            $lockedTable->forceFill($attributes)->save();

            return $lockedTable;
        });
    }

    public function delete(RestaurantTable $table): void
    {
        DB::transaction(function () use ($table): void {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            $this->ensureMayBeRemovedFromService($lockedTable);
            $lockedTable->delete();
        });
    }

    private function ensureMayBeRemovedFromService(RestaurantTable $table): void
    {
        $hasActiveSession = $table->diningSessions()
            ->where('status', DiningSessionStatus::Active->value)
            ->lockForUpdate()
            ->get(['id'])
            ->isNotEmpty();

        if ($hasActiveSession || $table->runtime_status !== RestaurantTableStatus::Available) {
            throw ValidationException::withMessages([
                'table' => __('table.admin.operation_blocked'),
            ]);
        }
    }
}
