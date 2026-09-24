<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCheckoutVoucherRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'voucher_code' => ['required', 'string', 'max:100'],
            'dining_session_id' => ['prohibited'],
            'bill_id' => ['prohibited'],
            'customer_id' => ['prohibited'],
            'voucher_id' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'discount' => ['prohibited'],
            'discount_amount' => ['prohibited'],
            'total' => ['prohibited'],
            'total_amount' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
