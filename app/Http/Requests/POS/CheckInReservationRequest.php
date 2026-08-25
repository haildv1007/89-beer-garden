<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.manage') === true
            && $this->user()?->can('dining-session.open') === true
            && $this->user()?->can('table.operate') === true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', 'integer', Rule::exists('restaurant_tables', 'id')->whereNull('deleted_at')],
            'status' => ['prohibited'], 'reservation_id' => ['prohibited'],
            'customer_id' => ['prohibited'], 'opened_by_employee_id' => ['prohibited'],
            'session_code' => ['prohibited'], 'started_at' => ['prohibited'],
            'ended_at' => ['prohibited'], 'completed_by_employee_id' => ['prohibited'],
            'checked_in_at' => ['prohibited'],
        ];
    }
}
