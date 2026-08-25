<?php

namespace App\Services\Billing;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyVoucherService
{
    public function __construct(private readonly BillCalculator $calculator) {}

    public function apply(Bill $bill, string $code): Bill
    {
        return $this->change($bill, strtoupper(trim($code)));
    }

    public function remove(Bill $bill): Bill
    {
        return $this->change($bill, null);
    }

    private function change(Bill $bill, ?string $code): Bill
    {
        return DB::transaction(function () use ($bill, $code): Bill {
            $billSnapshot = Bill::query()->findOrFail($bill->id);
            $sessionId = $billSnapshot->dining_session_id;
            $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
            $targetVoucher = $code === null ? null : Voucher::query()->where('code', $code)->first();
            $voucherIds = collect([$billSnapshot->voucher_id, $targetVoucher?->id])->filter()->unique()->sort()->values();
            $lockedVouchers = Voucher::withTrashed()->whereIn('id', $voucherIds)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);
            if ($session->status !== DiningSessionStatus::Active || $lockedBill->status === BillStatus::Paid
                || $lockedBill->voucher_id !== $billSnapshot->voucher_id) {
                throw ValidationException::withMessages(['bill' => __('billing.errors.paid_or_inactive')]);
            }

            $locked = $this->calculator->lockSessionOrdersAndItems($session->id);
            $subtotal = $this->calculator->subtotal($locked['items']);
            $voucher = $targetVoucher === null ? null : $lockedVouchers->get($targetVoucher->id);
            if ($code !== null && ($voucher === null || $voucher->code !== $code)) {
                throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
            }
            $discount = $voucher === null ? 0 : $this->calculator->discount($voucher, $subtotal);
            $total = $this->calculator->ensurePositiveTotal($subtotal, $discount);

            $lockedBill->forceFill([
                'voucher_id' => $voucher?->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
            ])->save();

            return $lockedBill->fresh();
        });
    }
}
