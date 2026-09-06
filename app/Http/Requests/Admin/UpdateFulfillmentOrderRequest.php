<?php

namespace App\Http\Requests\Admin;

use App\Models\FulfillmentOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFulfillmentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('order.create') === true;
    }

    public function rules(): array
    {
        /** @var FulfillmentOrder $order */
        $order = $this->route('fulfillmentOrder');

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => [
                Rule::requiredIf($order->fulfillment_type === FulfillmentOrder::TYPE_DELIVERY),
                'nullable',
                'string',
                'max:1000',
            ],
            'requested_for' => [
                'required',
                'date',
                'after:now',
                'before_or_equal:'.now()->addDays(7)->toDateTimeString(),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_option' => ['required', 'in:pay_on_receipt,bank_transfer'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'fulfillment_type' => ['prohibited'],
            'status' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'discount_amount' => ['prohibited'],
            'shipping_fee' => ['prohibited'],
            'total_amount' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('payment_option')) {
            /** @var FulfillmentOrder|null $order */
            $order = $this->route('fulfillmentOrder');
            $this->merge(['payment_option' => $order?->payment_option ?? FulfillmentOrder::PAYMENT_ON_RECEIPT]);
        }
    }
}
