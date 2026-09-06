<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiningSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true;
    }

    public function rules(): array
    {
        return [
            'guest_count' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_name' => ['nullable', 'string', 'max:255', 'required_with:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'required_with:customer_name'],
            'note' => ['nullable', 'string', 'max:2000'],
            'table_id' => ['prohibited'],
            'status' => ['prohibited'],
            'reservation_id' => ['prohibited'],
            'started_at' => ['prohibited'],
            'ended_at' => ['prohibited'],
            'opened_by_employee_id' => ['prohibited'],
        ];
    }
}
