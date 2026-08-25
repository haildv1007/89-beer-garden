<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenDiningSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.open') === true
            && $this->user()?->can('table.operate') === true;
    }

    public function rules(): array
    {
        return [
            'guest_count' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'note' => ['nullable', 'string', 'max:2000'],
            'status' => ['prohibited'], 'table_id' => ['prohibited'],
            'reservation_id' => ['prohibited'], 'opened_by_employee_id' => ['prohibited'],
            'session_code' => ['prohibited'], 'started_at' => ['prohibited'],
            'ended_at' => ['prohibited'], 'completed_by_employee_id' => ['prohibited'],
        ];
    }
}
