<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['slug'] = ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('categories', 'slug')->ignore($this->route('category'))];

        return $rules;
    }
}
