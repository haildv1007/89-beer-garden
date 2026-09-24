<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class CancelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'status' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'cancelled_by_employee_id' => ['prohibited'],
            'cancellation_reason' => ['prohibited'],
        ];
    }
}
