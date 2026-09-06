<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restaurant-table.manage') === true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('restaurant_tables', 'code')->ignore($this->route('restaurant_table')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'runtime_status' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
