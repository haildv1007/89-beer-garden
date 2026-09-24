<?php

namespace App\Services\Payment;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BusinessCode\BusinessCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletePaymentService
{
    public function __construct(
        private readonly PaymentTransactionState $state,
        private readonly BusinessCodeGenerator $codes,
    ) {}

    public function complete(
        Bill $bill,
        User $actor,
        string $method,
        ?string $reference,
        ?int $cashReceived = null,
    ): Payment {
        return DB::transaction(function () use ($bill, $actor, $method, $reference, $cashReceived): Payment {
            $locked = $this->state->lock($bill, $actor);
            $receivedAmount = null;
            $changeAmount = null;
            if ($method === Payment::METHOD_CASH) {
                $receivedAmount = $cashReceived ?? $locked['total'];
                if ($receivedAmount < $locked['total']) {
                    throw ValidationException::withMessages([
                        'cash_received' => 'Tiền khách đưa chưa đủ số tiền cần thanh toán.',
                    ]);
                }
                $changeAmount = $receivedAmount - $locked['total'];
            }
            $reservation = null;
            if ($locked['session']->reservation_id !== null) {
                $reservation = Reservation::query()->lockForUpdate()->findOrFail($locked['session']->reservation_id);
                if ($reservation->status !== ReservationStatus::CheckedIn) {
                    throw ValidationException::withMessages(['payment' => __('billing.errors.reservation_invalid')]);
                }
            }

            $now = now();
            $payment = Payment::query()->forceCreate([
                'payment_code' => $this->codes->next(BusinessCodeGenerator::PAYMENT),
                'bill_id' => $locked['bill']->id,
                'processed_by_employee_id' => $locked['employee']->id,
                'method' => $method,
                'amount' => $locked['total'],
                'received_amount' => $receivedAmount,
                'change_amount' => $changeAmount,
                'status' => PaymentStatus::Success,
                'transaction_reference' => $reference,
                'paid_at' => $now,
                'failed_at' => null,
                'failure_reason' => null,
            ]);
            $locked['bill']->forceFill(['status' => BillStatus::Paid, 'issued_at' => $now])->save();
            if ($locked['voucher'] !== null) {
                $locked['voucher']->forceFill(['used_count' => $locked['voucher']->used_count + 1])->save();
            }
            $locked['session']
                ->forceFill([
                    'status' => DiningSessionStatus::Completed,
                    'ended_at' => $now,
                    'completed_by_employee_id' => $locked['employee']->id,
                ])
                ->save();
            $locked['table']->forceFill(['runtime_status' => RestaurantTableStatus::Cleaning])->save();
            if ($reservation !== null) {
                $reservation->forceFill(['status' => ReservationStatus::Completed, 'completed_at' => $now])->save();
            }

            return $payment;
        });
    }
}
