<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaitingOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true && $this->user()?->can('order.update') === true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['prohibited'],
            'product_name' => ['prohibited'],
            'unit_price' => ['prohibited'],
            'price' => ['prohibited'],
            'line_total' => ['prohibited'],
            'status' => ['prohibited'],
            'order_id' => ['prohibited'],
            'cancelled_by_employee_id' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'cancellation_reason' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
