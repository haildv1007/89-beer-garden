<?php

namespace App\Http\Requests\POS;

use App\Models\FulfillmentOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompleteFulfillmentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payment.complete') === true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer'])],
            'cash_received' => ['nullable', 'integer', 'min:0'],
            'confirmed_received' => ['required', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var FulfillmentOrder|null $order */
                $order = $this->route('fulfillmentOrder');
                if ($order === null) {
                    return;
                }
                if (
                    $order->payment_option === FulfillmentOrder::PAYMENT_BANK_TRANSFER &&
                    $this->input('payment_method') !== 'bank_transfer'
                ) {
                    $validator->errors()->add('payment_method', 'Đơn này đã chọn chuyển khoản ngân hàng.');
                }
                if (
                    $this->input('payment_method') === 'cash' &&
                    $this->filled('cash_received') &&
                    $this->integer('cash_received') < $order->total_amount
                ) {
                    $validator->errors()->add('cash_received', 'Tiền khách đưa chưa đủ tổng thanh toán.');
                }
            },
        ];
    }
}
