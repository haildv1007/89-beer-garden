<?php

namespace App\Services\Payment;

use App\Models\FulfillmentOrder;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentWebhookTransaction;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProcessSePayWebhookService
{
    public function __construct(
        private readonly TypedSystemSettingResolver $settings,
        private readonly CompletePaymentService $completePayment,
    ) {}

    public function process(Request $request): void
    {
        $secret = $this->settings->sePayWebhookApiKey();
        $provided = $request->header('Authorization', '');
        if ($secret === null || ! hash_equals('Apikey '.$secret, $provided)) {
            throw new AccessDeniedHttpException('Invalid SePay webhook credentials.');
        }

        $data = $request->validate([
            'id' => ['required'],
            'transferType' => ['required', 'string'],
            'transferAmount' => ['required', 'numeric', 'min:0'],
            'accountNumber' => ['nullable', 'string', 'max:50'],
            'subAccount' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:2000'],
            'referenceCode' => ['nullable', 'string', 'max:255'],
            'transactionDate' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($data): void {
            $externalId = (string) $data['id'];
            $amount = (int) round((float) $data['transferAmount']);
            $bank = $this->settings->vietQr();
            $status = 'ignored';
            $order = null;
            $bill = null;
            $reference = strtoupper(trim((string) ($data['code'] ?? '')));
            $occurredAt = Carbon::parse($data['transactionDate']);
            $event = PaymentWebhookTransaction::query()->firstOrCreate(
                ['provider' => 'sepay', 'external_transaction_id' => $externalId],
                [
                    'status' => 'processing',
                    'amount' => $amount,
                    'account_number' => ($data['subAccount'] ?? null) ?: ($data['accountNumber'] ?? null),
                    'reference_code' => $data['referenceCode'] ?? $reference ?: null,
                    'occurred_at' => $occurredAt,
                    'content' => $data['content'] ?? null,
                    'payload' => $data,
                ],
            );
            if (! $event->wasRecentlyCreated) {
                return;
            }

            $receivingAccounts = array_filter([
                (string) ($data['accountNumber'] ?? ''),
                (string) ($data['subAccount'] ?? ''),
            ]);
            $matchesReceivingAccount = $bank !== null && collect($receivingAccounts)->contains(
                fn (string $account): bool => hash_equals($bank['account_number'], $account),
            );

            if (($data['transferType'] ?? null) === 'in' && $matchesReceivingAccount) {
                $order = $reference === '' ? null : FulfillmentOrder::query()
                    ->where('payment_reference', $reference)->lockForUpdate()->first();

                if ($order === null) {
                    $normalizedContent = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($data['content'] ?? '')));
                    $order = FulfillmentOrder::query()->where('payment_status', FulfillmentOrder::PAYMENT_UNPAID)
                        ->where('payment_option', FulfillmentOrder::PAYMENT_BANK_TRANSFER)
                        ->whereNotNull('payment_reference')->lockForUpdate()->get()
                        ->first(fn (FulfillmentOrder $candidate) => str_contains($normalizedContent, $candidate->payment_reference));
                }

                if ($order !== null) {
                    if ($order->payment_status === FulfillmentOrder::PAYMENT_PAID) {
                        $status = 'already_paid';
                    } elseif ($order->status === FulfillmentOrder::STATUS_REJECTED) {
                        $status = 'order_rejected';
                    } elseif ($order->payment_option !== FulfillmentOrder::PAYMENT_BANK_TRANSFER) {
                        $status = 'invalid_payment_method';
                    } elseif ($order->payment_expires_at === null || $occurredAt->greaterThan($order->payment_expires_at)) {
                        $status = 'expired';
                    } elseif ($occurredAt->lessThan($order->placed_at)) {
                        $status = 'before_order';
                    } elseif ($amount === (int) $order->total_amount) {
                        $order->forceFill([
                            'payment_status' => FulfillmentOrder::PAYMENT_PAID,
                            'payment_method' => 'bank_transfer',
                            'received_amount' => $amount,
                            'change_amount' => 0,
                            'paid_at' => now(),
                            'paid_by_employee_id' => null,
                        ])->save();
                        $status = 'matched';
                    } else {
                        $status = 'amount_mismatch';
                    }
                } else {
                    $bill = $reference === '' ? null : Bill::query()
                        ->where('payment_reference', $reference)->lockForUpdate()->first();
                    if ($bill === null) {
                        $normalizedContent = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($data['content'] ?? '')));
                        $bill = Bill::query()->where('status', 'unpaid')->whereNotNull('payment_reference')
                            ->lockForUpdate()->get()->first(fn (Bill $candidate) => str_contains($normalizedContent, $candidate->payment_reference));
                    }

                    if ($bill !== null) {
                        if ($bill->status->value === 'paid') {
                            $status = 'already_paid';
                        } elseif ($bill->payment_expires_at === null || $occurredAt->greaterThan($bill->payment_expires_at)) {
                            $status = 'expired';
                        } elseif ($amount !== $bill->total_amount) {
                            $status = 'amount_mismatch';
                        } elseif (! $bill->displayedBy) {
                            $status = 'missing_operator';
                        } else {
                            $this->completePayment->complete(
                                $bill,
                                $bill->displayedBy,
                                Payment::METHOD_BANK_TRANSFER,
                                (string) ($data['referenceCode'] ?? $externalId),
                            );
                            $status = 'matched';
                        }
                    }
                }
            }

            $event->forceFill([
                'fulfillment_order_id' => $order?->id,
                'bill_id' => $bill?->id,
                'status' => $status,
            ])->save();
        });
    }
}
