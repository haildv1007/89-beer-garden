<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectCartFulfillmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fulfillment_type' => ['required', Rule::in(['at_table', 'dine_in', 'pickup', 'delivery'])],
            'dining_session_id' => ['prohibited'],
            'table_id' => ['prohibited'],
            'shipping_fee' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'total' => ['prohibited'],
        ];
    }
}
