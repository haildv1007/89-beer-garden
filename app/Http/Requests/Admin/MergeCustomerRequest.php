<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MergeCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.view') === true;
    }

    public function rules(): array
    {
        return ['source_customer_id' => ['required', 'integer', 'exists:customers,id']];
    }
}
