<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['prohibited'], 'price' => ['prohibited'], 'product_name' => ['prohibited'],
            'status' => ['prohibited'], 'dining_session_id' => ['prohibited'], 'customer_id' => ['prohibited'],
        ];
    }
}
