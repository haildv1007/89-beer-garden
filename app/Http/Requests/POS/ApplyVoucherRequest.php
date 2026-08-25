<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class ApplyVoucherRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['voucher_code' => strtoupper(trim((string) $this->input('voucher_code')))]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('billing.view') === true
            && $this->user()?->can('voucher.apply') === true;
    }

    public function rules(): array
    {
        return [
            'voucher_code' => ['required', 'string', 'max:255'],
            'voucher_id' => ['prohibited'], 'subtotal' => ['prohibited'],
            'discount_amount' => ['prohibited'], 'total_amount' => ['prohibited'],
            'status' => ['prohibited'], 'used_count' => ['prohibited'],
        ];
    }
}
