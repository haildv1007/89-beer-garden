<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = match ($this->route()?->getName()) {
            'pos.order-items.cancel-waiting' => 'order-item.cancel-waiting',
            'pos.order-items.cancel-preparing' => 'order-item.cancel-preparing',
            default => null,
        };

        return $permission !== null && $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:1000'],
            'status' => ['prohibited'], 'cancelled_by_employee_id' => ['prohibited'],
            'cancelled_at' => ['prohibited'], 'order_id' => ['prohibited'],
            'product_id' => ['prohibited'], 'product_name' => ['prohibited'],
            'quantity' => ['prohibited'], 'unit_price' => ['prohibited'],
            'line_total' => ['prohibited'], 'note' => ['prohibited'],
            'created_at' => ['prohibited'], 'updated_at' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('cancellation_reason'))) {
            $this->merge(['cancellation_reason' => trim($this->input('cancellation_reason'))]);
        }
    }
}
