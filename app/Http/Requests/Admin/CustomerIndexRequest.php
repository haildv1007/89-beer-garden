<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
