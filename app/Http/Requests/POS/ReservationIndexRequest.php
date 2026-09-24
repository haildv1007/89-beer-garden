<?php

namespace App\Http\Requests\POS;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReservationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ReservationStatus::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['date_from', 'date_to'])) {
                    return;
                }

                $dateFrom = $this->string('date_from')->toString();
                $dateTo = $this->string('date_to')->toString();

                if ($dateFrom !== '' && $dateTo !== '' && $dateTo < $dateFrom) {
                    $validator->errors()->add('date_to', __('reservation.validation.date_range'));
                }
            },
        ];
    }
}
