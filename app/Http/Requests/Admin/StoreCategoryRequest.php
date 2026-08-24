<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('category.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('categories', 'slug')],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([Category::STATUS_ACTIVE, Category::STATUS_INACTIVE])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }
}
