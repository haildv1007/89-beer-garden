<?php

namespace App\Services\AIChat;

use App\Models\Customer;
use App\Models\User;

class CustomerContextBuilder
{
    /** @return array{authenticated:bool, records:list<array<string,mixed>>} */
    public function reservations(?User $user, ?string $code = null): array
    {
        if ($user === null) {
            return ['authenticated' => false, 'records' => []];
        }
        $customer = $this->customer($user);
        if ($customer === null) {
            return ['authenticated' => true, 'records' => []];
        }
        $records = $customer->reservations()
            ->when($code !== null, fn ($query) => $query->where('reservation_code', $code))
            ->latest('reservation_date')
            ->latest('reservation_time')
            ->limit((int) config('ai_chat.limits.customer_records', 5))
            ->get(['id', 'reservation_code', 'reservation_date', 'reservation_time', 'party_size', 'status'])
            ->map(fn ($reservation): array => [
                'code' => $reservation->reservation_code,
                'date' => $reservation->reservation_date?->toDateString(),
                'time' => $reservation->reservation_time,
                'party_size' => $reservation->party_size,
                'status' => $reservation->status->value,
            ])
            ->all();

        return ['authenticated' => true, 'records' => $records];
    }

    /** @return array{authenticated:bool, records:list<array<string,mixed>>} */
    public function orders(?User $user, ?string $code = null): array
    {
        if ($user === null) {
            return ['authenticated' => false, 'records' => []];
        }
        $customer = $this->customer($user);
        if ($customer === null) {
            return ['authenticated' => true, 'records' => []];
        }
        $limit = (int) config('ai_chat.limits.customer_records', 5);
        $dineIn = $customer->diningSessions()
            ->with('bill:id,dining_session_id,total_amount,status')
            ->when($code !== null, fn ($query) => $query->where('session_code', $code))
            ->latest('started_at')
            ->limit($limit)
            ->get(['id', 'session_code', 'status', 'started_at'])
            ->map(fn ($session): array => [
                'type' => 'dine_in',
                'code' => $session->session_code,
                'placed_at' => $session->started_at?->toIso8601String(),
                'status' => $session->status->value,
                'total' => $session->bill?->total_amount,
            ]);
        $fulfillment = $customer->fulfillmentOrders()
            ->when($code !== null, fn ($query) => $query->where('order_code', $code))
            ->latest('placed_at')
            ->limit($limit)
            ->get(['id', 'order_code', 'fulfillment_type', 'status', 'payment_status', 'total_amount', 'placed_at'])
            ->map(fn ($order): array => [
                'type' => $order->fulfillment_type,
                'code' => $order->order_code,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => (int) $order->total_amount,
            ]);

        return [
            'authenticated' => true,
            'records' => $dineIn->concat($fulfillment)
                ->sortByDesc('placed_at')
                ->take($limit)
                ->values()
                ->all(),
        ];
    }

    private function customer(?User $user): ?Customer
    {
        return $user?->customer()->first();
    }
}
