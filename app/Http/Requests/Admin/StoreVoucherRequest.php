<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoucherRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('voucher.manage') === true;
    }

    public function rules(): array
    {
        return $this->voucherRules();
    }

    /** @return array<string, array<int, mixed>> */
    protected function voucherRules(?Voucher $voucher = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('vouchers', 'code')->ignore($voucher)],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in([Voucher::TYPE_FIXED, Voucher::TYPE_PERCENTAGE])],
            'discount_value' => [
                'required',
                'integer',
                'min:0',
                'max:'.PHP_INT_MAX,
                Rule::when($this->input('discount_type') === Voucher::TYPE_PERCENTAGE, ['max:100']),
            ],
            'min_order_amount' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'max_discount_amount' => ['nullable', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'usage_limit' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'status' => ['required', Rule::in([Voucher::STATUS_ACTIVE, Voucher::STATUS_INACTIVE])],
            'used_count' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
