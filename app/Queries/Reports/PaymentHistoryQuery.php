<?php

namespace App\Queries\Reports;

use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\Payment;
use App\Models\PaymentWebhookTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentHistoryQuery
{
    /** @param array<string, mixed> $filters */
    public function run(CarbonImmutable $from, CarbonImmutable $to, array $filters): array
    {
        $tab = $filters['tab'] ?? 'history';
        $history = $this->history($from, $to, $filters);
        $review = $this->reconciliation($from, $to, $filters);
        $rows = $tab === 'reconciliation' ? $review : $history;

        return [
            'tab' => $tab,
            'rows' => $this->paginate($rows),
            'summary' => [
                'total' => $history->where('status', 'success')->sum('amount'),
                'count' => $history->where('status', 'success')->count(),
                'cash' => $history->where('status', 'success')->where('method', 'cash')->sum('amount'),
                'bank' => $history->where('status', 'success')->where('method', 'bank_transfer')->sum('amount'),
                'review' => $review->count(),
            ],
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function history(CarbonImmutable $from, CarbonImmutable $to, array $filters): Collection
    {
        $payments = Payment::query()
            ->with(['bill.diningSession.table', 'bill.diningSession.customer', 'processedBy'])
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($failed) use ($from, $to): void {
                        $failed->whereNull('paid_at')->whereBetween('failed_at', [$from, $to]);
                    })
                    ->orWhere(function ($other) use ($from, $to): void {
                        $other->whereNull('paid_at')->whereNull('failed_at')
                            ->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->when($filters['method'] ?? null, fn ($q, $value) => $q->where('method', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => in_array($value, ['success', 'failed', 'cancelled'], true) ? $q->where('status', $value) : $q->whereRaw('1 = 0'))
            ->when($filters['employee_id'] ?? null, fn ($q, $value) => $q->where('processed_by_employee_id', $value))
            ->get()->map(function (Payment $payment): object {
                $session = $payment->bill->diningSession;

                return (object) [
                    'kind' => 'payment', 'id' => $payment->id, 'source' => 'dine_in',
                    'occurred_at' => $payment->paid_at ?? $payment->failed_at ?? $payment->created_at,
                    'transaction_code' => $payment->payment_code, 'document_code' => $payment->bill->bill_code,
                    'order_session_code' => $session->session_code,
                    'order_session_url' => route('admin.dining-sessions.show', $session),
                    'customer' => $session->customer?->name ?? 'Khách tại bàn',
                    'context' => $session->table?->name ?? $session->table?->code ?? 'Tại bàn',
                    'method' => $payment->method, 'amount' => $payment->amount,
                    'status' => $payment->status->value, 'actor' => $payment->processedBy?->name ?? 'Hệ thống',
                    'employee_id' => $payment->processed_by_employee_id,
                    'bank_reference' => $payment->transaction_reference,
                    'url' => route('admin.bills.invoice', $payment->bill),
                ];
            });

        $orders = FulfillmentOrder::query()
            ->with(['paidByEmployee'])
            ->where('payment_status', FulfillmentOrder::PAYMENT_PAID)
            ->whereBetween('paid_at', [$from, $to])
            ->when($filters['source'] ?? null, fn ($q, $value) => $value === 'dine_in' ? $q->whereRaw('1 = 0') : $q->where('fulfillment_type', $value))
            ->when($filters['method'] ?? null, fn ($q, $value) => $q->where('payment_method', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $value === 'success' ? $q : $q->whereRaw('1 = 0'))
            ->when($filters['employee_id'] ?? null, fn ($q, $value) => $q->where('paid_by_employee_id', $value))
            ->get()->map(fn (FulfillmentOrder $order): object => (object) [
                'kind' => 'fulfillment', 'id' => $order->id, 'source' => $order->fulfillment_type,
                'occurred_at' => $order->paid_at, 'transaction_code' => $order->payment_reference ?: $order->order_code,
                'document_code' => $order->order_code, 'customer' => $order->customer_name,
                'order_session_code' => $order->order_code,
                'order_session_url' => route('admin.fulfillment-orders.show', $order),
                'context' => $order->fulfillment_type === 'pickup' ? 'Nhận tại quán' : 'Giao tận nơi',
                'method' => $order->payment_method ?? 'other', 'amount' => $order->total_amount,
                'status' => 'success', 'actor' => $order->paidByEmployee?->name ?? 'SePay tự động',
                'employee_id' => $order->paid_by_employee_id, 'bank_reference' => null,
                'url' => route('admin.fulfillment-orders.show', $order),
            ]);

        $rows = ($filters['source'] ?? null) === 'dine_in' ? $payments : (($filters['source'] ?? null) ? $orders : $payments->concat($orders));

        return $this->search($rows, $filters)->sortByDesc('occurred_at')->values();
    }

    /** @param array<string, mixed> $filters */
    private function reconciliation(CarbonImmutable $from, CarbonImmutable $to, array $filters): Collection
    {
        $query = PaymentWebhookTransaction::query()->with(['bill.diningSession.table', 'fulfillmentOrder'])
            ->whereBetween('occurred_at', [$from, $to])->whereNotIn('status', ['matched'])
            ->when(($filters['method'] ?? null) && $filters['method'] !== 'bank_transfer', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($filters['employee_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($filters['status'] ?? null, fn ($q, $value) => ! in_array($value, ['success', 'failed', 'cancelled'], true) ? $q->where('status', $value) : $q->whereRaw('1 = 0'));

        $rows = $query->get()->map(function (PaymentWebhookTransaction $event): object {
            $order = $event->fulfillmentOrder;
            $bill = $event->bill;
            $source = $order?->fulfillment_type ?? 'dine_in';

            return (object) [
                'kind' => 'webhook', 'id' => $event->id, 'source' => $source,
                'occurred_at' => $event->occurred_at, 'transaction_code' => $event->external_transaction_id,
                'document_code' => $order?->order_code ?? $bill?->bill_code ?? $event->reference_code ?? 'Chưa khớp đơn',
                'order_session_code' => $order?->order_code ?? $bill?->diningSession?->session_code,
                'order_session_url' => $order
                    ? route('admin.fulfillment-orders.show', $order)
                    : ($bill ? route('admin.dining-sessions.show', $bill->diningSession) : null),
                'customer' => $order?->customer_name ?? 'Giao dịch ngân hàng',
                'context' => $order ? ($source === 'pickup' ? 'Nhận tại quán' : 'Giao tận nơi') : ($bill?->diningSession?->table?->name ?? 'Chưa xác định'),
                'method' => 'bank_transfer', 'amount' => $event->amount, 'status' => $event->status,
                'actor' => 'SePay', 'employee_id' => null, 'bank_reference' => $event->reference_code,
                'url' => $order ? route('admin.fulfillment-orders.show', $order) : ($bill ? route('admin.bills.show', $bill) : null),
            ];
        });

        if ($source = $filters['source'] ?? null) {
            $rows = $rows->where('source', $source);
        }

        return $this->search($rows, $filters)->sortByDesc('occurred_at')->values();
    }

    /** @param array<string, mixed> $filters */
    private function search(Collection $rows, array $filters): Collection
    {
        $term = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        if ($term === '') {
            return $rows;
        }

        return $rows->filter(fn ($row) => str_contains(mb_strtolower(implode(' ', [
            $row->transaction_code, $row->document_code, $row->customer, $row->context, $row->bank_reference,
        ])), $term));
    }

    private function paginate(Collection $rows): LengthAwarePaginator
    {
        $page = max(1, (int) request('page', 1));

        return new LengthAwarePaginator($rows->forPage($page, 20)->values(), $rows->count(), 20, $page, [
            'path' => request()->url(), 'query' => request()->query(),
        ]);
    }
}
