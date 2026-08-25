<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OperationalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('report.view') === true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('preset')) {
            $this->merge(['preset' => $this->hasAny(['from', 'to']) ? 'custom' : '30']);
        }
    }

    public function rules(): array
    {
        return [
            'preset' => ['required', Rule::in(['today', '7', '30', 'custom'])],
            'from' => ['nullable', 'required_if:preset,custom', 'prohibited_unless:preset,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:preset,custom', 'prohibited_unless:preset,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
            'timezone' => ['prohibited'],
            'sort' => ['prohibited'],
            'order_by' => ['prohibited'],
            'limit' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('preset') !== 'custom' || $validator->errors()->isNotEmpty()) {
                return;
            }
            $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('from'));
            $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('to'));
            if ($from->diffInDays($to) > 365) {
                $validator->errors()->add('to', __('report.validation.max_range'));
            }
        }];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    public function dateRange(): array
    {
        $timezone = (string) config('app.timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $preset = (string) $this->validated('preset');

        [$from, $to] = match ($preset) {
            'today' => [$today, $today],
            '7' => [$today->subDays(6), $today],
            'custom' => [
                CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->validated('from'), $timezone),
                CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->validated('to'), $timezone),
            ],
            default => [$today->subDays(29), $today],
        };

        return [$from->startOfDay(), $to->endOfDay(), $preset];
    }
}
