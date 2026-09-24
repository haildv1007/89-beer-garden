<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.view') === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'account' => ['nullable', Rule::in(['member', 'guest'])],
            'sort' => ['nullable', Rule::in(['total_orders', 'completed_orders'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
