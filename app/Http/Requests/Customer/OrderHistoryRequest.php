<?php

namespace App\Http\Requests\Customer;

use App\Enums\DiningSessionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrderHistoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::enum(DiningSessionStatus::class)],
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
                    $validator->errors()->add('to', __('order_history.validation.range'));
                }
            },
        ];
    }
}
