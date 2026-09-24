<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerActivityService
{
    public function timeline(Customer $customer, array $filters): LengthAwarePaginator
    {
        $type = $filters['type'] ?? null;
        $from = isset($filters['from']) ? CarbonImmutable::parse($filters['from'])->startOfDay() : null;
        $to = isset($filters['to']) ? CarbonImmutable::parse($filters['to'])->endOfDay() : null;
        $activities = collect();

        if (! $type || $type === 'reservation') {
            $customer
                ->reservations()
                ->with('table:id,code,name')
                ->get()
                ->each(function ($reservation) use ($activities): void {
                    $activities->push([
                        'type' => 'reservation',
                        'label' => 'Đặt bàn',
                        'code' => $reservation->reservation_code,
                        'occurred_at' => $reservation->created_at,
                        'scheduled_at' => $reservation->reservation_date->setTimeFromTimeString(
                            $reservation->reservation_time,
                        ),
                        'status' => $reservation->status->value,
                        'description' => $reservation->party_size.
                            ' khách'.
                            ($reservation->table ? ' · '.$reservation->table->name : ''),
                        'amount' => null,
                    ]);
                });
        }

        if (! $type || $type === 'dine_in') {
            $customer
                ->diningSessions()
                ->with(['table:id,code,name', 'bill:id,dining_session_id,total_amount,status'])
                ->get()
                ->each(function ($session) use ($activities): void {
                    $activities->push([
                        'type' => 'dine_in',
                        'label' => 'Tại quán',
                        'code' => $session->session_code,
                        'occurred_at' => $session->started_at,
                        'scheduled_at' => null,
                        'status' => $session->status->value,
                        'description' => ($session->table?->name ?? 'Chưa xác định bàn').' · '.$session->guest_count.' khách',
                        'amount' => $session->bill?->total_amount,
                    ]);
                });
        }

        if (! $type || in_array($type, ['pickup', 'delivery'], true)) {
            $customer
                ->fulfillmentOrders()
                ->whereIn('fulfillment_type', $type ? [$type] : ['pickup', 'delivery'])
                ->get()
                ->each(function ($order) use ($activities): void {
                    $activities->push([
                        'type' => $order->fulfillment_type,
                        'label' => $order->fulfillment_type === 'delivery' ? 'Giao hàng' : 'Đến lấy',
                        'code' => $order->order_code,
                        'occurred_at' => $order->placed_at,
                        'scheduled_at' => $order->requested_for,
                        'status' => $order->status,
                        'description' => $order->fulfillment_type === 'delivery'
                                ? ($order->delivery_address ?:
                                'Giao tận nơi')
                                : 'Khách nhận tại quán',
                        'amount' => $order->total_amount,
                    ]);
                });
        }

        $activities = $activities
            ->filter(
                fn (array $activity) => (! $from || $activity['occurred_at']?->greaterThanOrEqualTo($from)) &&
                    (! $to || $activity['occurred_at']?->lessThanOrEqualTo($to)),
            )
            ->sortByDesc('occurred_at')
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;

        return new LengthAwarePaginator($activities->forPage($page, $perPage), $activities->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    public function metrics(Customer $customer, int $dineInSpending): array
    {
        $fulfillmentSpending = (int) $customer
            ->fulfillmentOrders()
            ->where('status', '!=', 'rejected')
            ->sum('total_amount');
        $lastActivity = Collection::make([
            $customer->reservations()->max('created_at'),
            $customer->diningSessions()->max('started_at'),
            $customer->fulfillmentOrders()->max('placed_at'),
        ])
            ->filter()
            ->max();

        return [
            'total_activities' => $customer->reservations_count +
                $customer->dining_sessions_count +
                $customer->pickup_orders_count +
                $customer->delivery_orders_count,
            'spending' => $dineInSpending + $fulfillmentSpending,
            'last_activity_at' => $lastActivity ? CarbonImmutable::parse($lastActivity) : null,
        ];
    }
}
