<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['prohibited'],
            'dining_session_id' => ['prohibited'],
            'customer_id' => ['prohibited'],
            'created_by_customer_id' => ['prohibited'],
            'created_by_employee_id' => ['prohibited'],
            'source' => ['prohibited'],
            'order_code' => ['prohibited'],
            'ordered_at' => ['prohibited'],
            'price' => ['prohibited'],
            'product_name' => ['prohibited'],
            'unit_price' => ['prohibited'],
            'line_total' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
