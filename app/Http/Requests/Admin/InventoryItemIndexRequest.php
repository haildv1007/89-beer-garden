<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.view') === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([InventoryItem::STATUS_ACTIVE, InventoryItem::STATUS_INACTIVE])],
            'low_stock' => ['nullable', 'boolean'],
        ];
    }
}
