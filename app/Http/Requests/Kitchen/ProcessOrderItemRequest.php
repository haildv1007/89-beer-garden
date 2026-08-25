<?php

namespace App\Http\Requests\Kitchen;

use Illuminate\Foundation\Http\FormRequest;

class ProcessOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = match ($this->route()?->getName()) {
            'kitchen.order-items.start-preparing' => 'order-item.mark-preparing',
            'kitchen.order-items.mark-ready' => 'order-item.mark-ready',
            default => null,
        };

        return $permission !== null && $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return $this->protectedFields();
    }

    /** @return array<string, list<string>> */
    private function protectedFields(): array
    {
        return [
            'status' => ['prohibited'], 'order_id' => ['prohibited'],
            'product_id' => ['prohibited'], 'product_name' => ['prohibited'],
            'quantity' => ['prohibited'], 'unit_price' => ['prohibited'],
            'line_total' => ['prohibited'], 'note' => ['prohibited'],
            'cancelled_by_employee_id' => ['prohibited'], 'cancelled_at' => ['prohibited'],
            'cancellation_reason' => ['prohibited'], 'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
