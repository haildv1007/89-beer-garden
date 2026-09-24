<?php

namespace App\Http\Requests\Admin;

use App\Enums\RestaurantTableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestaurantTableIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restaurant-table.manage') === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(RestaurantTableStatus::class)],
            'active' => ['nullable', Rule::in(['1', '0'])],
            'min_capacity' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
        ];
    }
}
