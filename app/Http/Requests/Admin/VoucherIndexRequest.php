<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('voucher.manage') === true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Voucher::STATUS_ACTIVE, Voucher::STATUS_INACTIVE])],
        ];
    }
}
