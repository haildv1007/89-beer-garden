<?php

namespace App\Services\RestaurantTable;

use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkRestaurantTableAvailableService
{
    public function handle(RestaurantTable $table): RestaurantTable
    {
        return DB::transaction(function () use ($table): RestaurantTable {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            $hasActiveSession = $lockedTable->diningSessions()
                ->where('status', DiningSessionStatus::Active->value)
                ->lockForUpdate()
                ->get(['id'])
                ->isNotEmpty();

            if ($lockedTable->runtime_status !== RestaurantTableStatus::Cleaning || $hasActiveSession) {
                throw ValidationException::withMessages([
                    'table' => __('table.pos.cleaning_transition_invalid'),
                ]);
            }

            $lockedTable->forceFill(['runtime_status' => RestaurantTableStatus::Available])->save();

            return $lockedTable;
        });
    }
}
