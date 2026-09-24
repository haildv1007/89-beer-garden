<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('inventory.view') === true;
    }

    public function rules(): array
    {
        return $this->itemRules();
    }

    /** @return array<string, array<int, mixed>> */
    protected function itemRules(?InventoryItem $item = null): array
    {
        $currentProductId = $item?->product_id;

        return [
            'sku' => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'sku')->ignore($item)],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:100'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'status' => ['required', Rule::in([InventoryItem::STATUS_ACTIVE, InventoryItem::STATUS_INACTIVE])],
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($currentProductId): void {
                    $query->where(function ($query): void {
                        $query->whereNull('deleted_at')->where('status', Product::STATUS_ACTIVE);
                    });
                    if ($currentProductId !== null) {
                        $query->orWhere('id', $currentProductId);
                    }
                }),
                Rule::unique('inventory_items', 'product_id')->ignore($item),
            ],
            'current_stock' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
