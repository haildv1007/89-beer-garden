<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.view') === true
            && $this->user()?->can('inventory.stock-movement.create') === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'note' => ['nullable', 'string', 'max:2000'],
            'inventory_item_id' => ['prohibited'], 'stock_before' => ['prohibited'],
            'stock_after' => ['prohibited'], 'created_by_employee_id' => ['prohibited'],
            'created_at' => ['prohibited'], 'current_stock' => ['prohibited'],
        ];
    }
}
