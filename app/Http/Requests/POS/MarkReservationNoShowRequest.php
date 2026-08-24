<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class MarkReservationNoShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.mark-no-show') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['prohibited'], 'table_id' => ['prohibited'],
            'customer_id' => ['prohibited'], 'confirmed_by_employee_id' => ['prohibited'],
            'confirmed_at' => ['prohibited'], 'checked_in_at' => ['prohibited'],
            'completed_at' => ['prohibited'], 'no_show_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
        ];
    }
}
