<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryItem;

class UpdateInventoryItemRequest extends StoreInventoryItemRequest
{
    public function rules(): array
    {
        /** @var InventoryItem $item */
        $item = $this->route('inventory_item');

        return $this->itemRules($item);
    }
}
