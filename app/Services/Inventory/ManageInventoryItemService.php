<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class ManageInventoryItemService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): InventoryItem
    {
        return DB::transaction(function () use ($attributes): InventoryItem {
            $item = new InventoryItem;
            $item->forceFill($attributes + ['current_stock' => 0])->save();

            return $item;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(InventoryItem $item, array $attributes): InventoryItem
    {
        return DB::transaction(function () use ($item, $attributes): InventoryItem {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $locked->forceFill($attributes)->save();

            return $locked;
        });
    }
}
