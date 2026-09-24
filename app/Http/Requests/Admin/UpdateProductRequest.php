<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') === true;
    }

    public function rules(): array
    {
        $rules = $this->baseRules();
        $rules['slug'] = [
            'required',
            'string',
            'max:255',
            'alpha_dash:ascii',
            Rule::unique('products', 'slug')->ignore($this->route('product')),
        ];
        $rules['price'] = ['prohibited'];

        return $rules;
    }
}
