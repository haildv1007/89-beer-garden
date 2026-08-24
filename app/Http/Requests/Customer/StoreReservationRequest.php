<?php

namespace App\Http\Requests\Customer;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null
            || $this->user()->can('customer.reservation.view-own');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'party_size' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'note' => ['nullable', 'string', 'max:5000'],
            'status' => ['prohibited'],
            'table_id' => ['prohibited'],
            'customer_id' => ['prohibited'],
            'confirmed_by_employee_id' => ['prohibited'],
            'confirmed_at' => ['prohibited'],
            'checked_in_at' => ['prohibited'],
            'completed_at' => ['prohibited'],
            'no_show_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['reservation_date', 'reservation_time'])) {
                return;
            }

            $scheduledAt = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $this->string('reservation_date').' '.$this->string('reservation_time'),
                config('app.timezone'),
            );

            if ($scheduledAt === false || $scheduledAt->lessThanOrEqualTo(now())) {
                $validator->errors()->add('reservation_time', __('reservation.validation.future_time'));
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'phone'] as $field) {
            if (is_string($this->{$field})) {
                $this->merge([$field => trim($this->{$field})]);
            }
        }
    }
}
