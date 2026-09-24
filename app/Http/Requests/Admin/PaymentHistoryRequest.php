<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class PaymentHistoryRequest extends OperationalReportRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'tab' => ['nullable', Rule::in(['history', 'reconciliation'])],
            'q' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', Rule::in(['dine_in', 'pickup', 'delivery'])],
            'method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'other'])],
            'status' => ['nullable', Rule::in([
                'success', 'failed', 'cancelled', 'expired', 'amount_mismatch', 'ignored',
                'already_paid', 'order_rejected', 'invalid_payment_method', 'before_order',
                'missing_operator', 'processing',
            ])],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
