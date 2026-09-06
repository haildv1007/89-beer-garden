<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true && $this->user()?->can('order.create') === true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.note' => ['nullable', 'string', 'max:2000'],
            'order_code' => ['prohibited'],
            'dining_session_id' => ['prohibited'],
            'created_by_employee_id' => ['prohibited'],
            'created_by_customer_id' => ['prohibited'],
            'source' => ['prohibited'],
            'ordered_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'items.*.product_name' => ['prohibited'],
            'items.*.unit_price' => ['prohibited'],
            'items.*.price' => ['prohibited'],
            'items.*.line_total' => ['prohibited'],
            'items.*.status' => ['prohibited'],
            'items.*.cancelled_by_employee_id' => ['prohibited'],
            'items.*.cancelled_at' => ['prohibited'],
            'items.*.cancellation_reason' => ['prohibited'],
            'items.*.created_at' => ['prohibited'],
            'items.*.updated_at' => ['prohibited'],
        ];
    }
}
