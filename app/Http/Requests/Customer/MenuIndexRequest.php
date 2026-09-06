<?php

namespace App\Http\Requests\Customer;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('categories', 'slug')->where(
                    fn ($query) => $query->where('status', Category::STATUS_ACTIVE)->whereNull('deleted_at'),
                ),
            ],
        ];
    }
}
