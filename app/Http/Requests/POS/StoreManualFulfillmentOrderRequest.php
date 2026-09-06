<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualFulfillmentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('order.create') === true;
    }

    public function rules(): array
    {
        return [
            'fulfillment_type' => ['required', 'in:pickup,delivery'],
            'payment_option' => ['required', 'in:pay_on_receipt,bank_transfer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'delivery_address' => ['nullable', 'required_if:fulfillment_type,delivery', 'string', 'max:1000'],
            'requested_for' => ['required', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('payment_option')) {
            $this->merge(['payment_option' => 'pay_on_receipt']);
        }
    }
}
