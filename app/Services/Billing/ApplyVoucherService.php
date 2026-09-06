<?php

namespace App\Services\Billing;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Models\Voucher;
use App\Services\CustomerOrder\CustomerOrderingCapability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyVoucherService
{
    public function __construct(
        private readonly BillCalculator $calculator,
        private readonly CustomerOrderingCapability $capability,
    ) {}

    public function apply(Bill $bill, string $code): Bill
    {
        return $this->change($bill, strtoupper(trim($code)));
    }

    public function remove(Bill $bill): Bill
    {
        return $this->change($bill, null);
    }

    public function applyForCustomer(Bill $bill, string $code, int $sessionId): Bill
    {
        return $this->change($bill, strtoupper(trim($code)), $sessionId);
    }

    public function removeForCustomer(Bill $bill, int $sessionId): Bill
    {
        return $this->change($bill, null, $sessionId);
    }

    private function change(Bill $bill, ?string $code, ?int $customerSessionId = null): Bill
    {
        return DB::transaction(function () use ($bill, $code, $customerSessionId): Bill {
            $billSnapshot = Bill::query()->findOrFail($bill->id);
            $sessionId = $billSnapshot->dining_session_id;
            $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
            if (
                $customerSessionId !== null &&
                ($session->id !== $customerSessionId || ! $this->capability->enabled(true))
            ) {
                throw ValidationException::withMessages(['context' => __('customer_order.errors.context_invalid')]);
            }
            $targetVoucher = $code === null ? null : Voucher::query()->where('code', $code)->first();
            $voucherIds = collect([$billSnapshot->voucher_id, $targetVoucher?->id])
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $lockedVouchers = Voucher::withTrashed()
                ->whereIn('id', $voucherIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);
            if (
                $session->status !== DiningSessionStatus::Active ||
                $lockedBill->status === BillStatus::Paid ||
                ($customerSessionId !== null && $lockedBill->status !== BillStatus::Unpaid) ||
                $lockedBill->voucher_id !== $billSnapshot->voucher_id
            ) {
                throw ValidationException::withMessages(['bill' => __('billing.errors.paid_or_inactive')]);
            }

            $locked = $this->calculator->lockSessionOrdersAndItems($session->id);
            $table = $customerSessionId === null ? null : $session->table()->lockForUpdate()->first();
            if (
                $customerSessionId !== null &&
                (! $table?->is_active || $table->runtime_status !== RestaurantTableStatus::Occupied)
            ) {
                throw ValidationException::withMessages(['context' => __('customer_order.errors.context_invalid')]);
            }
            $subtotal = $this->calculator->subtotal($locked['items']);
            $voucher = $targetVoucher === null ? null : $lockedVouchers->get($targetVoucher->id);
            if ($code !== null && ($voucher === null || $voucher->code !== $code)) {
                throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
            }
            $discount = $voucher === null ? 0 : $this->calculator->discount($voucher, $subtotal);
            $total = $this->calculator->ensurePositiveTotal($subtotal, $discount);

            $lockedBill
                ->forceFill([
                    'voucher_id' => $voucher?->id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                ])
                ->save();

            return $lockedBill->fresh();
        });
    }
}
