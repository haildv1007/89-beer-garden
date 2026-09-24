<?php

namespace App\Services\CustomerHistory;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\FulfillmentOrder;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerHistoryService
{
    public function paidTransactions(Customer $customer, array $filters): LengthAwarePaginator
    {
        $successfulPaymentIds = DB::table('payments')->selectRaw('MIN(id)')
            ->where('status', PaymentStatus::Success->value)->groupBy('bill_id');
        $dineIn = DB::table('bills')->join('dining_sessions', 'dining_sessions.id', '=', 'bills.dining_session_id')
            ->join('payments', 'payments.bill_id', '=', 'bills.id')
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'dining_sessions.table_id')
            ->where('dining_sessions.customer_id', $customer->id)->where('bills.status', BillStatus::Paid->value)
            ->whereIn('payments.id', $successfulPaymentIds)
            ->selectRaw("'dine_in' as source_type, bills.id as source_id, bills.bill_code as code, restaurant_tables.name as place_name, restaurant_tables.code as place_code, payments.method as method, payments.paid_at as paid_at, bills.total_amount as total_amount");
        $outside = DB::table('fulfillment_orders')->where('customer_id', $customer->id)
            ->whereIn('fulfillment_type', ['pickup', 'delivery'])->where('payment_status', 'paid')->whereNotNull('paid_at')
            ->selectRaw('fulfillment_type as source_type, id as source_id, order_code as code, NULL as place_name, NULL as place_code, payment_method as method, paid_at, total_amount');
        $query = DB::query()->fromSub($dineIn->unionAll($outside), 'paid_transactions');
        $q = trim((string) ($filters['q'] ?? ''));

        return $query->when($q !== '', fn ($builder) => $builder->where(fn ($nested) => $nested
            ->where('code', 'like', "%{$q}%")->orWhere('place_name', 'like', "%{$q}%")->orWhere('place_code', 'like', "%{$q}%")))
            ->when($filters['method'] ?? null, fn ($builder, string $method) => $builder->where('method', $method))
            ->when($filters['from'] ?? null, fn ($builder, string $from) => $builder->where('paid_at', '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($builder, string $to) => $builder->where('paid_at', '<=', CarbonImmutable::parse($to)->endOfDay()))
            ->orderByDesc('paid_at')->orderByDesc('source_id')->paginate(15)->withQueryString();
    }

    public function paidBills(Customer $customer, array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return Bill::query()
            ->where('status', BillStatus::Paid->value)
            ->whereHas('diningSession', fn (Builder $query) => $query->where('customer_id', $customer->id))
            ->whereHas('successfulPayment', fn (Builder $query) => $query->where('status', PaymentStatus::Success->value))
            ->with([
                'diningSession.table:id,code,name',
                'successfulPayment:id,bill_id,method,amount,status,paid_at',
            ])
            ->when($q !== '', fn (Builder $query) => $query->where(
                fn (Builder $nested) => $nested->where('bill_code', 'like', "%{$q}%")
                    ->orWhereHas('diningSession.table', fn (Builder $table) => $table
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")),
            ))
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query
                ->whereHas('successfulPayment', fn (Builder $payment) => $payment->where('method', $method)))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query
                ->whereHas('successfulPayment', fn (Builder $payment) => $payment->where(
                    'paid_at', '>=', CarbonImmutable::parse($from, config('app.timezone'))->startOfDay(),
                )))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query
                ->whereHas('successfulPayment', fn (Builder $payment) => $payment->where(
                    'paid_at', '<=', CarbonImmutable::parse($to, config('app.timezone'))->endOfDay(),
                )))
            ->orderByDesc(Payment::query()->select('paid_at')->whereColumn('payments.bill_id', 'bills.id')
                ->where('status', PaymentStatus::Success->value)->limit(1))
            ->paginate(15)
            ->withQueryString();
    }

    public function ownedPaidBill(Customer $customer, Bill $bill): Bill
    {
        return Bill::query()
            ->whereKey($bill->id)
            ->where('status', BillStatus::Paid->value)
            ->whereHas('diningSession', fn (Builder $query) => $query->where('customer_id', $customer->id))
            ->whereHas('successfulPayment', fn (Builder $query) => $query->where('status', PaymentStatus::Success->value))
            ->with([
                'voucher' => fn ($query) => $query->withTrashed(),
                'diningSession.table:id,code,name',
                'diningSession.orders' => fn ($query) => $query->oldest('ordered_at')->oldest('id'),
                'diningSession.orders.items' => fn ($query) => $query->where('status', '!=', OrderItemStatus::Cancelled->value),
                'successfulPayment:id,bill_id,payment_code,method,amount,received_amount,change_amount,status,paid_at,transaction_reference',
            ])
            ->firstOrFail();
    }

    public function sessions(Customer $customer, array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return DiningSession::query()
            ->where('customer_id', $customer->id)
            ->with([
                'table:id,code,name',
                'orders' => fn ($query) => $query->with('items')->orderBy('ordered_at'),
                'bill.voucher:id,code',
                'bill.successfulPayment:id,bill_id,method,amount,status,paid_at',
            ])
            ->withCount('orders')
            ->when(
                $q !== '',
                fn (Builder $query) => $query->where(
                    fn (Builder $nested) => $nested
                        ->where('session_code', 'like', "%{$q}%")
                        ->orWhereHas('orders', fn (Builder $orders) => $orders->where('order_code', 'like', "%{$q}%")),
                ),
            )
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(
                $filters['from'] ?? null,
                fn (Builder $query, string $from) => $query->where(
                    'started_at',
                    '>=',
                    CarbonImmutable::parse($from, config('app.timezone'))->startOfDay(),
                ),
            )
            ->when(
                $filters['to'] ?? null,
                fn (Builder $query, string $to) => $query->where(
                    'started_at',
                    '<=',
                    CarbonImmutable::parse($to, config('app.timezone'))->endOfDay(),
                ),
            )
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function ownedSession(Customer $customer, DiningSession $session): DiningSession
    {
        return DiningSession::query()
            ->whereKey($session->id)
            ->where('customer_id', $customer->id)
            ->with([
                'table:id,code,name',
                'orders' => fn ($query) => $query->with('items')->orderBy('ordered_at'),
                'bill.voucher:id,code',
                'bill.successfulPayment:id,bill_id,method,amount,status,paid_at',
            ])
            ->firstOrFail();
    }

    public function overview(Customer $customer): array
    {
        $paidSessionIds = DiningSession::query()
            ->where('customer_id', $customer->id)
            ->where('status', DiningSessionStatus::Completed->value)
            ->whereHas(
                'bill',
                fn (Builder $bill) => $bill
                    ->where('status', BillStatus::Paid->value)
                    ->whereHas(
                        'payments',
                        fn (Builder $payment) => $payment->where('status', PaymentStatus::Success->value),
                    ),
            )
            ->pluck('id');
        $paymentIds = Payment::query()
            ->selectRaw('MIN(payments.id)')
            ->join('bills', 'bills.id', '=', 'payments.bill_id')
            ->whereIn('bills.dining_session_id', $paidSessionIds)
            ->where('payments.status', PaymentStatus::Success->value)
            ->groupBy('payments.bill_id');
        $payments = Payment::query()->whereIn('id', $paymentIds);

        $outside = $customer->fulfillmentOrders()->where('payment_status', 'paid')->whereNotNull('paid_at');

        return [
            'sessions' => $paidSessionIds->count() + (clone $outside)->count(),
            'orders' => Order::query()
                ->whereIn('dining_session_id', $paidSessionIds)
                ->whereHas(
                    'items',
                    fn (Builder $items) => $items->where('status', '!=', OrderItemStatus::Cancelled->value),
                )
                ->count(),
            'spending' => (int) (clone $payments)->sum('amount') + (int) (clone $outside)->sum('total_amount'),
            'last_used_at' => collect([(clone $payments)->max('paid_at'), (clone $outside)->max('paid_at')])->filter()->max(),
        ];
    }

    public function ownedFulfillmentOrder(Customer $customer, FulfillmentOrder $order, bool $paidOnly = false): FulfillmentOrder
    {
        return $customer->fulfillmentOrders()->whereKey($order->id)
            ->whereIn('fulfillment_type', ['pickup', 'delivery'])
            ->when($paidOnly, fn (Builder $query) => $query->where('payment_status', 'paid')->whereNotNull('paid_at'))
            ->with(['items', 'voucher' => fn ($query) => $query->withTrashed(), 'paidByEmployee:id,name'])
            ->firstOrFail();
    }
}
