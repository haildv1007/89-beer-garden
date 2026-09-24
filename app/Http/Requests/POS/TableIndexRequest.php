<?php

namespace App\Http\Requests\POS;

use App\Enums\RestaurantTableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TableIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('table.view') === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(RestaurantTableStatus::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', Rule::in(['all', '1', '0'])],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
        ];
    }
}
