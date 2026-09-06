<?php

namespace App\Services\Payment;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Billing\BillCalculator;
use Illuminate\Validation\ValidationException;

class PaymentTransactionState
{
    public function __construct(private readonly BillCalculator $calculator) {}

    /** @return array{session: DiningSession, bill: Bill, employee: Employee, table: RestaurantTable, voucher: Voucher|null, total: int} */
    public function lock(Bill $bill, User $actor): array
    {
        $billSnapshot = Bill::query()->findOrFail($bill->id);
        $sessionId = $billSnapshot->dining_session_id;
        $session = DiningSession::query()->lockForUpdate()->findOrFail($sessionId);
        $voucher =
            $billSnapshot->voucher_id === null
                ? null
                : Voucher::withTrashed()->lockForUpdate()->findOrFail($billSnapshot->voucher_id);
        $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);

        if (
            $session->status !== DiningSessionStatus::Active ||
            $lockedBill->status !== BillStatus::Unpaid ||
            $lockedBill->voucher_id !== $voucher?->id
        ) {
            throw ValidationException::withMessages(['payment' => __('billing.errors.paid_or_inactive')]);
        }
        if (
            $lockedBill
                ->payments()
                ->where('status', PaymentStatus::Success->value)
                ->lockForUpdate()
                ->get(['id'])
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages(['payment' => __('billing.errors.duplicate_payment')]);
        }

        $locked = $this->calculator->lockSessionOrdersAndItems($session->id);
        $subtotal = $this->calculator->subtotal($locked['items']);
        $discount = $voucher === null ? 0 : $this->calculator->discount($voucher, $subtotal);
        $total = $this->calculator->ensurePositiveTotal($subtotal, $discount);

        $employee = Employee::query()
            ->where('user_id', $actor->id)
            ->where('status', EmployeeStatus::Active->value)
            ->lockForUpdate()
            ->firstOrFail();
        $table = RestaurantTable::query()->lockForUpdate()->findOrFail($session->table_id);
        if (
            ! $table->is_active ||
            $table->runtime_status !== RestaurantTableStatus::Occupied ||
            $table->activeDiningSession()->whereKey($session->id)->doesntExist()
        ) {
            throw ValidationException::withMessages(['payment' => __('billing.errors.table_invalid')]);
        }

        $lockedBill
            ->forceFill([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
            ])
            ->save();

        return compact('session', 'employee', 'table', 'voucher', 'total') + ['bill' => $lockedBill];
    }
}
