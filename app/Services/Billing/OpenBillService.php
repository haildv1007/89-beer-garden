<?php

namespace App\Services\Billing;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpenBillService
{
    public function __construct(private readonly BillCalculator $calculator) {}

    public function open(DiningSession $session): Bill
    {
        return DB::transaction(function () use ($session): Bill {
            $lockedSession = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($lockedSession->status !== DiningSessionStatus::Active) {
                throw ValidationException::withMessages(['bill' => __('billing.errors.session_inactive')]);
            }

            $billSnapshot = Bill::query()->where('dining_session_id', $lockedSession->id)->first();
            $voucher = $billSnapshot?->voucher_id === null
                ? null
                : Voucher::withTrashed()->lockForUpdate()->findOrFail($billSnapshot->voucher_id);
            $bill = $billSnapshot === null ? null : Bill::query()->lockForUpdate()->findOrFail($billSnapshot->id);
            if ($bill?->status === BillStatus::Paid) {
                throw ValidationException::withMessages(['bill' => __('billing.errors.paid')]);
            }

            $locked = $this->calculator->lockSessionOrdersAndItems($lockedSession->id);
            $subtotal = $this->calculator->subtotal($locked['items']);
            $discount = 0;
            if ($voucher !== null) {
                $discount = $this->calculator->discount($voucher, $subtotal);
            }
            $total = $this->calculator->ensurePositiveTotal($subtotal, $discount);

            $bill ??= new Bill;
            $bill->forceFill([
                'bill_code' => $bill->bill_code ?? 'BIL-'.Str::ulid(),
                'dining_session_id' => $lockedSession->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'status' => BillStatus::Unpaid,
                'issued_at' => null,
            ])->save();

            return $bill->fresh();
        });
    }
}
