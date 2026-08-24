<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') === true
            && $this->user()?->can('product.update-price') === true;
    }

    public function rules(): array
    {
        return $this->baseRules() + ['price' => ['required', 'integer', 'min:0']];
    }

    protected function baseRules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string', 'max:5000'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
            'is_available' => ['required', 'boolean'],
        ];
    }
}
