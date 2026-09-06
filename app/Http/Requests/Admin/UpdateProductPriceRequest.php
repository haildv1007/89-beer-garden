<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') === true && $this->user()?->can('product.update-price') === true;
    }

    public function rules(): array
    {
        return ['price' => ['required', 'integer', 'min:0']];
    }
}
