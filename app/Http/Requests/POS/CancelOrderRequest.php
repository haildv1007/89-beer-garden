<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('order-item.cancel-waiting') === true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:1000'],
            'status' => ['prohibited'],
            'items' => ['prohibited'],
            'cancelled_by_employee_id' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('cancellation_reason'))) {
            $this->merge(['cancellation_reason' => trim($this->input('cancellation_reason'))]);
        }
    }
}
