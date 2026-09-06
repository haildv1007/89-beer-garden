<?php

namespace App\Http\Requests\POS;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'reservation_date' => ['required', 'date_format:Y-m-d'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'party_size' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['array', 'max:30'],
            'items.*.product_id' => ['nullable', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['reservation_date', 'reservation_time'])) {
                    return;
                }

                $arrival = CarbonImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $this->string('reservation_date').' '.$this->string('reservation_time'),
                    config('app.timezone'),
                );

                if ($arrival->isPast()) {
                    $validator->errors()->add('reservation_time', __('reservation.validation.future_time'));
                }
            },
        ];
    }
}
