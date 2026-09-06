<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Services\BusinessCode\BusinessCodeGenerator;
use Illuminate\Support\Facades\DB;

class RecordFailedPaymentService
{
    public function __construct(
        private readonly PaymentTransactionState $state,
        private readonly BusinessCodeGenerator $codes,
    ) {}

    public function record(Bill $bill, User $actor, string $method, ?string $reference, string $reason): Payment
    {
        return DB::transaction(function () use ($bill, $actor, $method, $reference, $reason): Payment {
            $locked = $this->state->lock($bill, $actor);

            return Payment::query()->forceCreate([
                'payment_code' => $this->codes->next(BusinessCodeGenerator::PAYMENT),
                'bill_id' => $locked['bill']->id,
                'processed_by_employee_id' => $locked['employee']->id,
                'method' => $method,
                'amount' => $locked['total'],
                'status' => PaymentStatus::Failed,
                'transaction_reference' => $reference,
                'paid_at' => null,
                'failed_at' => now(),
                'failure_reason' => $reason,
            ]);
        });
    }
}
