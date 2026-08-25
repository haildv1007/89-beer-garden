<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class MarkOrderItemServedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('order-item.mark-served') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['prohibited'], 'quantity' => ['prohibited'],
            'product_id' => ['prohibited'], 'product_name' => ['prohibited'],
            'unit_price' => ['prohibited'], 'line_total' => ['prohibited'],
            'cancelled_by_employee_id' => ['prohibited'], 'cancelled_at' => ['prohibited'],
            'cancellation_reason' => ['prohibited'],
        ];
    }
}
