<?php

namespace App\Queries\Reports;

use App\Enums\BillStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalReportQuery
{
    /** @return array<string, mixed> */
    public function run(CarbonInterface $from, CarbonInterface $to): array
    {
        $payments = $this->paidPayments($from, $to);
        $revenue = (int) (clone $payments)->sum('report_payments.amount');
        $validOrderCount = $this->validOrders($from, $to)->count();

        return [
            'revenue' => $revenue,
            'validOrderCount' => $validOrderCount,
            'averageOrderValue' => $validOrderCount === 0 ? 0 : intdiv($revenue, $validOrderCount),
            'topProducts' => $this->topProducts($from, $to),
            'reservationStats' => $this->reservationStats($from, $to),
            'payments' => $this->paymentDetails($from, $to),
        ];
    }

    private function firstSuccessfulPaymentIds(): Builder
    {
        return DB::table('payments')->selectRaw('MIN(id)')
            ->where('status', PaymentStatus::Success->value)->groupBy('bill_id');
    }

    private function paidPayments(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return DB::table('payments as report_payments')
            ->join('bills', 'bills.id', '=', 'report_payments.bill_id')
            ->whereIn('report_payments.id', $this->firstSuccessfulPaymentIds())
            ->where('bills.status', BillStatus::Paid->value)
            ->whereBetween('report_payments.paid_at', [$from, $to]);
    }

    private function validOrders(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return DB::table('orders')->whereExists(function (Builder $query): void {
            $query->selectRaw('1')->from('order_items')
                ->whereColumn('order_items.order_id', 'orders.id')
                ->where('order_items.status', '!=', OrderItemStatus::Cancelled->value);
        })->whereExists(function (Builder $query) use ($from, $to): void {
            $query->selectRaw('1')->from('bills')
                ->join('payments as report_payments', 'report_payments.bill_id', '=', 'bills.id')
                ->whereColumn('bills.dining_session_id', 'orders.dining_session_id')
                ->whereIn('report_payments.id', $this->firstSuccessfulPaymentIds())
                ->where('bills.status', BillStatus::Paid->value)
                ->whereBetween('report_payments.paid_at', [$from, $to]);
        });
    }

    /** @return Collection<int, object> */
    private function topProducts(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('bills', 'bills.dining_session_id', '=', 'orders.dining_session_id')
            ->join('payments as report_payments', 'report_payments.bill_id', '=', 'bills.id')
            ->whereIn('report_payments.id', $this->firstSuccessfulPaymentIds())
            ->where('bills.status', BillStatus::Paid->value)
            ->where('order_items.status', '!=', OrderItemStatus::Cancelled->value)
            ->whereBetween('report_payments.paid_at', [$from, $to])
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, MIN(order_items.product_name) as product_name, SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as line_revenue')
            ->orderByDesc('quantity')->orderByDesc('line_revenue')->orderBy('order_items.product_id')
            ->limit(10)->get();
    }

    /** @return Collection<int, object> */
    private function reservationStats(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return DB::table('reservations')->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->orderBy('status')->get();
    }

    private function paymentDetails(CarbonInterface $from, CarbonInterface $to): LengthAwarePaginator
    {
        return $this->paidPayments($from, $to)
            ->join('dining_sessions', 'dining_sessions.id', '=', 'bills.dining_session_id')
            ->join('restaurant_tables', 'restaurant_tables.id', '=', 'dining_sessions.table_id')
            ->join('employees', 'employees.id', '=', 'report_payments.processed_by_employee_id')
            ->select(['report_payments.id', 'report_payments.payment_code', 'report_payments.method',
                'report_payments.amount', 'report_payments.paid_at', 'bills.bill_code', 'bills.subtotal',
                'bills.discount_amount', 'bills.total_amount', 'dining_sessions.session_code',
                'restaurant_tables.code as table_code', 'employees.name as employee_name'])
            ->orderByDesc('report_payments.paid_at')->orderByDesc('report_payments.id')
            ->paginate(30)->withQueryString();
    }
}
