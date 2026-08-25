<?php

namespace App\Services\Inventory;

use App\Enums\EmployeeStatus;
use App\Enums\StockMovementType;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStockService
{
    /** @var list<StockMovementType> */
    private const INCREASE_TYPES = [
        StockMovementType::Import,
        StockMovementType::AdjustmentIn,
        StockMovementType::Return,
    ];

    public function record(InventoryItem $item, User $actor, StockMovementType $type, int $quantity, ?string $note): StockMovement
    {
        return DB::transaction(function () use ($item, $actor, $type, $quantity, $note): StockMovement {
            $lockedItem = InventoryItem::withTrashed()->lockForUpdate()->findOrFail($item->id);
            if ($lockedItem->trashed() || $lockedItem->status !== InventoryItem::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['inventory_item' => __('inventory.errors.item_inactive')]);
            }

            $employee = Employee::query()->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)->lockForUpdate()->firstOrFail();
            $lockedUser = User::query()->lockForUpdate()->findOrFail($actor->id);
            if ($lockedUser->status !== User::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['inventory_item' => __('inventory.errors.actor_inactive')]);
            }
            $before = $lockedItem->current_stock;
            if (in_array($type, self::INCREASE_TYPES, true)) {
                if ($quantity > PHP_INT_MAX - $before) {
                    throw ValidationException::withMessages(['quantity' => __('inventory.errors.overflow')]);
                }
                $after = $before + $quantity;
            } else {
                if ($quantity > $before) {
                    throw ValidationException::withMessages(['quantity' => __('inventory.errors.insufficient')]);
                }
                $after = $before - $quantity;
            }

            $movement = StockMovement::query()->forceCreate([
                'inventory_item_id' => $lockedItem->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $note,
                'created_by_employee_id' => $employee->id,
                'created_at' => now(),
            ]);
            $lockedItem->forceFill(['current_stock' => $after])->save();

            return $movement;
        });
    }
}
