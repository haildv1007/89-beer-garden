<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PlacePickupOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'requested_for' => [
                'required',
                'date',
                'after:now',
                'before_or_equal:'.now()->addDays(7)->toDateTimeString(),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_option' => ['required', 'in:pay_on_receipt,bank_transfer'],
            'fulfillment_type' => ['prohibited'],
            'status' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'discount_amount' => ['prohibited'],
            'shipping_fee' => ['prohibited'],
            'total_amount' => ['prohibited'],
            'voucher_id' => ['prohibited'],
            'customer_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('payment_option')) {
            $this->merge(['payment_option' => 'pay_on_receipt']);
        }
    }
}
