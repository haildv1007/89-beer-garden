<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class RejectFulfillmentOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }
}
