<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'], 'price' => ['prohibited'],
            'product_name' => ['prohibited'], 'status' => ['prohibited'], 'dining_session_id' => ['prohibited'],
            'customer_id' => ['prohibited'], 'employee_id' => ['prohibited'], 'source' => ['prohibited'],
        ];
    }
}
