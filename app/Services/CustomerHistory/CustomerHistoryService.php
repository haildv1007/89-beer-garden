<?php

namespace App\Services\CustomerHistory;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerHistoryService
{
    public function sessions(Customer $customer, array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return DiningSession::query()->where('customer_id', $customer->id)
            ->with(['table:id,code,name', 'orders' => fn ($query) => $query->with('items')->orderBy('ordered_at'),
                'bill.voucher:id,code', 'bill.successfulPayment:id,bill_id,method,amount,status,paid_at'])
            ->withCount('orders')->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested
            ->where('session_code', 'like', "%{$q}%")
            ->orWhereHas('orders', fn (Builder $orders) => $orders->where('order_code', 'like', "%{$q}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('started_at', '>=', CarbonImmutable::parse($from, config('app.timezone'))->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('started_at', '<=', CarbonImmutable::parse($to, config('app.timezone'))->endOfDay()))
            ->orderByDesc('started_at')->orderByDesc('id')->paginate(15)->withQueryString();
    }

    public function ownedSession(Customer $customer, DiningSession $session): DiningSession
    {
        return DiningSession::query()->whereKey($session->id)->where('customer_id', $customer->id)
            ->with(['table:id,code,name', 'orders' => fn ($query) => $query->with('items')->orderBy('ordered_at'),
                'bill.voucher:id,code', 'bill.successfulPayment:id,bill_id,method,amount,status,paid_at'])->firstOrFail();
    }

    public function overview(Customer $customer): array
    {
        $paidSessionIds = DiningSession::query()->where('customer_id', $customer->id)
            ->where('status', DiningSessionStatus::Completed->value)
            ->whereHas('bill', fn (Builder $bill) => $bill->where('status', BillStatus::Paid->value)
                ->whereHas('payments', fn (Builder $payment) => $payment->where('status', PaymentStatus::Success->value)))
            ->pluck('id');
        $paymentIds = Payment::query()->selectRaw('MIN(payments.id)')->join('bills', 'bills.id', '=', 'payments.bill_id')
            ->whereIn('bills.dining_session_id', $paidSessionIds)->where('payments.status', PaymentStatus::Success->value)
            ->groupBy('payments.bill_id');
        $payments = Payment::query()->whereIn('id', $paymentIds);

        return ['sessions' => $paidSessionIds->count(),
            'orders' => Order::query()->whereIn('dining_session_id', $paidSessionIds)
                ->whereHas('items', fn (Builder $items) => $items->where('status', '!=', OrderItemStatus::Cancelled->value))->count(),
            'spending' => (int) (clone $payments)->sum('amount'), 'last_used_at' => (clone $payments)->max('paid_at')];
    }
}
