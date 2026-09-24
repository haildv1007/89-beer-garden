<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustomerHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.view') === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['reservation', 'dine_in', 'pickup', 'delivery'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('from') || ! $this->filled('to')) {
                    return;
                }

                $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('from'));
                $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('to'));
                if ($from->greaterThan($to) || $from->diffInDays($to) > 366) {
                    $validator->errors()->add('to', 'Khoảng thời gian không hợp lệ hoặc dài quá 366 ngày.');
                }
            },
        ];
    }
}
