<?php

namespace App\Queries\Reports;

use App\Enums\BillStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalReportQuery
{
    public function run(CarbonInterface $from, CarbonInterface $to, string $preset = 'custom'): array
    {
        $events = $this->revenueEvents($from, $to);
        [$previousFrom, $previousTo] = $this->previousRange($from, $to, $preset);
        $previous = $this->revenueEvents($previousFrom, $previousTo);
        $sessions = $this->sessionMetrics($from, $to);
        $previousSessions = $this->sessionMetrics($previousFrom, $previousTo);
        $revenue = (int) $events->sum('amount');
        $dineInRevenue = (int) $events->where('source', 'dine_in')->sum('amount');
        $count = $events->count();
        $guests = (int) $sessions['guests'];
        $previousRevenue = (int) $previous->sum('amount');
        $previousDineInRevenue = (int) $previous->where('source', 'dine_in')->sum('amount');
        $validOrderCount = $this->validOrders($from, $to)->count();
        $reservations = $this->reservationMetrics($from, $to);
        $reconciliationCount = DB::table('payment_webhook_transactions')->whereBetween('occurred_at', [$from, $to])->whereNotIn('status', ['matched', 'already_paid'])->count();

        return [
            'revenue' => $revenue,
            'validOrderCount' => $validOrderCount,
            'averageOrderValue' => $validOrderCount ? intdiv($revenue, $validOrderCount) : 0,
            'topProducts' => $this->topProducts($from, $to),
            'reservationStats' => $reservations['statuses'],
            'payments' => $this->paymentDetails($from, $to),
            'summary' => [
                'revenue' => $this->metric($revenue, $previousRevenue),
                'invoices' => $this->metric($count, $previous->count()),
                'average_invoice' => $this->metric($count ? intdiv($revenue, $count) : 0, $previous->count() ? intdiv($previousRevenue, $previous->count()) : 0),
                'guests' => $this->metric($guests, (int) $previousSessions['guests']),
                'revenue_per_guest' => $this->metric($guests ? intdiv($dineInRevenue, $guests) : 0, $previousSessions['guests'] ? intdiv($previousDineInRevenue, (int) $previousSessions['guests']) : 0),
            ],
            'trend' => $this->trend($events, $from, $to),
            'sourceBreakdown' => $this->breakdown($events, 'source', $revenue),
            'paymentBreakdown' => $this->breakdown($events, 'method', $revenue),
            'sessions' => $sessions,
            'topTables' => $this->topTables($from, $to),
            'cancelledProducts' => $this->cancelledProducts($from, $to),
            'reservations' => $reservations,
            'fulfillment' => $this->fulfillmentMetrics($from, $to),
            'reconciliationCount' => $reconciliationCount,
            'alerts' => $this->alerts($reconciliationCount),
        ];
    }

    private function firstSuccessfulPaymentIds(): Builder
    {
        return DB::table('payments')->selectRaw('MIN(id)')->where('status', PaymentStatus::Success->value)->groupBy('bill_id');
    }

    private function paidPayments(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return DB::table('payments as report_payments')->join('bills', 'bills.id', '=', 'report_payments.bill_id')
            ->whereIn('report_payments.id', $this->firstSuccessfulPaymentIds())->where('bills.status', BillStatus::Paid->value)
            ->whereBetween('report_payments.paid_at', [$from, $to]);
    }

    private function revenueEvents(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $dineIn = $this->paidPayments($from, $to)->get(['report_payments.amount', 'report_payments.method', 'report_payments.paid_at as occurred_at'])
            ->map(fn ($row) => (object) ['amount' => (int) $row->amount, 'method' => $row->method, 'occurred_at' => $row->occurred_at, 'source' => 'dine_in']);
        $outside = DB::table('fulfillment_orders')->where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to])
            ->get(['total_amount as amount', 'payment_method as method', 'paid_at as occurred_at', 'fulfillment_type as source'])
            ->map(fn ($row) => (object) ['amount' => (int) $row->amount, 'method' => $row->method ?: 'other', 'occurred_at' => $row->occurred_at, 'source' => $row->source]);

        return $dineIn->concat($outside)->values();
    }

    private function validOrders(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return DB::table('orders')->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('order_items')->whereColumn('order_items.order_id', 'orders.id')->where('order_items.status', '!=', OrderItemStatus::Cancelled->value))
            ->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('bills')->join('payments as report_payments', 'report_payments.bill_id', '=', 'bills.id')->whereColumn('bills.dining_session_id', 'orders.dining_session_id')->whereIn('report_payments.id', $this->firstSuccessfulPaymentIds())->where('bills.status', BillStatus::Paid->value)->whereBetween('report_payments.paid_at', [$from, $to]));
    }

    private function topProducts(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $inside = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->join('bills', 'bills.dining_session_id', '=', 'orders.dining_session_id')->join('payments as report_payments', 'report_payments.bill_id', '=', 'bills.id')->leftJoin('products', 'products.id', '=', 'order_items.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->whereIn('report_payments.id', $this->firstSuccessfulPaymentIds())->where('bills.status', BillStatus::Paid->value)->where('order_items.status', '!=', OrderItemStatus::Cancelled->value)->whereBetween('report_payments.paid_at', [$from, $to])->get(['order_items.product_id', 'order_items.product_name', 'order_items.quantity', 'order_items.line_total', 'categories.name as category_name']);
        $outside = DB::table('fulfillment_order_items')->join('fulfillment_orders', 'fulfillment_orders.id', '=', 'fulfillment_order_items.fulfillment_order_id')->leftJoin('products', 'products.id', '=', 'fulfillment_order_items.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->where('fulfillment_orders.payment_status', 'paid')->where('fulfillment_order_items.status', '!=', OrderItemStatus::Cancelled->value)->whereBetween('fulfillment_orders.paid_at', [$from, $to])->get(['fulfillment_order_items.product_id', 'fulfillment_order_items.product_name', 'fulfillment_order_items.quantity', 'fulfillment_order_items.line_total', 'categories.name as category_name']);

        return $inside->concat($outside)->groupBy('product_id')->map(fn (Collection $rows) => (object) ['product_id' => $rows->first()->product_id, 'product_name' => $rows->last()->product_name, 'category_name' => $rows->first()->category_name ?: 'Khác', 'quantity' => (int) $rows->sum('quantity'), 'line_revenue' => (int) $rows->sum('line_total')])->sortBy([['quantity', 'desc'], ['line_revenue', 'desc']])->take(10)->values();
    }

    private function cancelledProducts(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return DB::table('order_items')->where('status', OrderItemStatus::Cancelled->value)->whereBetween('cancelled_at', [$from, $to])->get(['product_id', 'product_name', 'quantity', 'line_total'])->groupBy('product_id')->map(fn (Collection $rows) => (object) ['product_name' => $rows->last()->product_name, 'quantity' => (int) $rows->sum('quantity'), 'value' => (int) $rows->sum('line_total')])->sortByDesc('quantity')->take(5)->values();
    }

    private function sessionMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('dining_sessions')->whereBetween('started_at', [$from, $to])->get();
        $completed = $rows->where('status', 'completed');
        $durations = $completed->filter(fn ($row) => $row->ended_at)->map(fn ($row) => Carbon::parse($row->started_at)->diffInMinutes(Carbon::parse($row->ended_at)));
        $valid = $rows->where('status', '!=', 'cancelled');

        return ['total' => $rows->count(), 'completed' => $completed->count(), 'cancelled' => $rows->where('status', 'cancelled')->count(), 'active' => $rows->where('status', 'active')->count(), 'guests' => (int) $valid->sum('guest_count'), 'average_guests' => $valid->count() ? round($valid->avg('guest_count'), 1) : 0, 'average_minutes' => $durations->count() ? (int) round($durations->avg()) : 0, 'average_rounds' => $rows->count() ? round(DB::table('orders')->whereIn('dining_session_id', $rows->pluck('id'))->count() / $rows->count(), 1) : 0, 'transfers' => DB::table('dining_session_table_transfers')->whereIn('dining_session_id', $rows->pluck('id'))->count()];
    }

    private function topTables(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->paidPayments($from, $to)->join('dining_sessions', 'dining_sessions.id', '=', 'bills.dining_session_id')->join('restaurant_tables', 'restaurant_tables.id', '=', 'dining_sessions.table_id')->groupBy('restaurant_tables.id', 'restaurant_tables.name', 'restaurant_tables.code')->selectRaw('restaurant_tables.name, restaurant_tables.code, COUNT(DISTINCT dining_sessions.id) sessions, SUM(dining_sessions.guest_count) guests, SUM(report_payments.amount) revenue')->orderByDesc('revenue')->limit(5)->get()->map(function ($row) {
            $row->average = $row->sessions ? intdiv((int) $row->revenue, (int) $row->sessions) : 0;

            return $row;
        });
    }

    private function reservationMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('reservations')->whereBetween('reservation_date', [$from->toDateString(), $to->toDateString()])->get();
        $statuses = $rows->groupBy('status')->map(fn (Collection $items, string $status) => (object) ['status' => $status, 'total' => $items->count()])->values();
        $eligible = $rows->whereIn('status', ['checked-in', 'completed', 'no-show'])->count();
        $arrived = $rows->whereIn('status', ['checked-in', 'completed'])->count();

        return ['total' => $rows->count(), 'statuses' => $statuses, 'arrived' => $arrived, 'no_show' => $rows->where('status', 'no-show')->count(), 'late' => $rows->where('status', 'late')->count(), 'cancelled' => $rows->whereIn('status', ['cancelled', 'rejected'])->count(), 'arrival_rate' => $eligible ? round($arrived * 100 / $eligible, 1) : 0];
    }

    private function fulfillmentMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('fulfillment_orders')->whereBetween('placed_at', [$from, $to])->get();
        $paid = DB::table('fulfillment_orders')->where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to])->get();

        return ['total' => $rows->count(), 'pickup' => $rows->where('fulfillment_type', 'pickup')->count(), 'delivery' => $rows->where('fulfillment_type', 'delivery')->count(), 'confirmed' => $rows->where('status', 'confirmed')->count(), 'rejected' => $rows->where('status', 'rejected')->count(), 'paid' => $paid->count(), 'revenue' => (int) $paid->sum('total_amount'), 'shipping_fees' => (int) $paid->sum('shipping_fee'), 'average' => $paid->count() ? intdiv((int) $paid->sum('total_amount'), $paid->count()) : 0, 'prepaid' => $paid->where('payment_option', 'bank_transfer')->count(), 'on_receipt' => $paid->where('payment_option', 'pay_on_receipt')->count()];
    }

    private function trend(Collection $events, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $hourly = $from->isSameDay($to);
        $format = $hourly ? 'H:00' : 'Y-m-d';
        $grouped = $events->groupBy(fn ($row) => Carbon::parse($row->occurred_at)->format($format));
        $points = collect();
        $cursor = $from->copy()->startOf($hourly ? 'hour' : 'day');
        $end = $to->copy()->startOf($hourly ? 'hour' : 'day');
        while ($cursor->lte($end)) {
            $key = $cursor->format($format);
            $points->push((object) ['label' => $cursor->format($hourly ? 'H\h' : 'd/m'), 'amount' => (int) ($grouped->get($key)?->sum('amount') ?? 0)]);
            $cursor = $hourly ? $cursor->addHour() : $cursor->addDay();
        }
        $max = max(1, (int) $points->max('amount'));

        return $points->map(function ($point) use ($max) {
            $point->percent = round($point->amount * 100 / $max, 1);

            return $point;
        });
    }

    private function breakdown(Collection $events, string $key, int $revenue): Collection
    {
        return $events->groupBy($key)->map(fn (Collection $rows, string $name) => (object) ['name' => $name, 'amount' => (int) $rows->sum('amount'), 'count' => $rows->count(), 'percent' => $revenue ? round($rows->sum('amount') * 100 / $revenue, 1) : 0])->sortByDesc('amount')->values();
    }

    private function alerts(int $reconciliation): array
    {
        $late = DB::table('reservations')->where('status', 'late')->count();
        $long = DB::table('dining_sessions')->where('status', 'active')->where('started_at', '<', now()->subHours(4))->count();
        $waiting = DB::table('fulfillment_orders')->where('status', 'pending')->where('placed_at', '<', now()->subMinutes(15))->count();

        return array_values(array_filter([$reconciliation ? ['count' => $reconciliation, 'title' => 'Giao dịch cần đối soát', 'url' => route('admin.reports.payments', ['tab' => 'reconciliation'])] : null, $late ? ['count' => $late, 'title' => 'Khách trễ check-in', 'url' => route('admin.reservations.index', ['status' => 'late'])] : null, $long ? ['count' => $long, 'title' => 'Phiên mở trên 4 giờ', 'url' => route('admin.dining-sessions.index', ['status' => 'active'])] : null, $waiting ? ['count' => $waiting, 'title' => 'Đơn chờ quá 15 phút', 'url' => route('admin.fulfillment-orders.index', ['status' => 'pending'])] : null]));
    }

    private function metric(int $value, int $previous): array
    {
        return ['value' => $value, 'previous' => $previous, 'change' => $previous === 0 ? null : round(($value - $previous) * 100 / $previous, 1)];
    }

    private function previousRange(CarbonInterface $from, CarbonInterface $to, string $preset): array
    {
        $seconds = $from->diffInSeconds($to) + 1;
        $previousTo = $from->copy()->subSecond();

        return [$previousTo->copy()->subSeconds($seconds - 1), $previousTo];
    }

    private function paymentDetails(CarbonInterface $from, CarbonInterface $to): LengthAwarePaginator
    {
        return $this->paidPayments($from, $to)->join('dining_sessions', 'dining_sessions.id', '=', 'bills.dining_session_id')->join('restaurant_tables', 'restaurant_tables.id', '=', 'dining_sessions.table_id')->join('employees', 'employees.id', '=', 'report_payments.processed_by_employee_id')->select(['report_payments.id', 'report_payments.payment_code', 'report_payments.method', 'report_payments.amount', 'report_payments.paid_at', 'bills.bill_code', 'bills.subtotal', 'bills.discount_amount', 'bills.total_amount', 'dining_sessions.session_code', 'restaurant_tables.code as table_code', 'employees.name as employee_name'])->orderByDesc('report_payments.paid_at')->paginate(30)->withQueryString();
    }
}
