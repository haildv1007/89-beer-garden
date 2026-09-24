<?php

namespace App\Services\Billing;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\Voucher;

class RefreshOpenBillService
{
    public function __construct(private readonly BillCalculator $calculator) {}

    public function refreshForLockedSession(int $sessionId): void
    {
        $bill = Bill::query()->where('dining_session_id', $sessionId)->lockForUpdate()->first();
        if ($bill === null || $bill->status !== BillStatus::Unpaid) {
            return;
        }
        $voucher =
            $bill->voucher_id === null ? null : Voucher::withTrashed()->lockForUpdate()->findOrFail($bill->voucher_id);
        $locked = $this->calculator->lockSessionOrdersAndItems($sessionId);
        $subtotal = $this->calculator->subtotal($locked['items']);
        $discount = $voucher === null ? 0 : $this->calculator->discount($voucher, $subtotal);
        $bill
            ->forceFill([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $this->calculator->ensurePositiveTotal($subtotal, $discount),
            ])
            ->save();
    }
}
