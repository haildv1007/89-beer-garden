<?php

namespace App\Http\Requests\POS;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('billing.view') === true
            && $this->user()?->can('payment.complete') === true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in([Payment::METHOD_CASH, Payment::METHOD_BANK_TRANSFER, Payment::METHOD_OTHER])],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'confirmed_received' => ['required', 'accepted'],
            'amount' => ['prohibited'], 'processed_by_employee_id' => ['prohibited'],
            'status' => ['prohibited'], 'paid_at' => ['prohibited'],
            'failed_at' => ['prohibited'], 'failure_reason' => ['prohibited'],
        ];
    }
}
