<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManageDiningSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true && $this->user()?->can('order.update') === true;
    }

    public function rules(): array
    {
        return [
            'guest_count' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_name' => ['nullable', 'string', 'max:255', 'required_with:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'required_with:customer_name'],
            'session_note' => ['nullable', 'string', 'max:2000'],
            'order_note' => ['nullable', 'string', 'max:2000'],
            'existing_items' => ['nullable', 'array', 'max:200'],
            'existing_items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'existing_items.*.note' => ['nullable', 'string', 'max:2000'],
            'existing_items.*.cancel' => ['nullable', 'boolean'],
            'existing_items.*.cancellation_reason' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:existing_items.*.cancel,1',
            ],
            'new_items' => ['nullable', 'array', 'max:100'],
            'new_items.*.product_id' => ['required', 'integer', 'distinct'],
            'new_items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'new_items.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
