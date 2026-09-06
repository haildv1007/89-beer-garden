<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Customer\MergeCustomerService;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

class ConsolidateDuplicateCustomers extends Command
{
    protected $signature = 'customers:consolidate-duplicates {--apply : Thực hiện gộp dữ liệu}';

    protected $description = 'Tìm và gộp hồ sơ khách hàng trùng số điện thoại chuẩn hóa';

    public function handle(MergeCustomerService $merger): int
    {
        $groups = Customer::query()
            ->withCount([
                'reservations as completed_reservations_count' => fn ($query) => $query->where('status', 'completed'),
                'diningSessions as completed_dining_sessions_count' => fn ($query) => $query->where(
                    'status',
                    'completed',
                ),
                'fulfillmentOrders as completed_fulfillment_orders_count' => fn ($query) => $query
                    ->where('status', 'confirmed')
                    ->whereHas('items', fn ($items) => $items->whereIn('status', ['ready', 'served']))
                    ->whereDoesntHave(
                        'items',
                        fn ($items) => $items->whereNotIn('status', ['ready', 'served', 'cancelled']),
                    ),
            ])
            ->get()
            ->groupBy(fn (Customer $customer) => PhoneNumber::normalize($customer->phone))
            ->filter(fn ($customers, $phone) => $phone && $customers->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('Không có hồ sơ trùng số điện thoại.');

            return self::SUCCESS;
        }

        foreach ($groups as $phone => $customers) {
            $ranked = $customers
                ->sort(function (Customer $left, Customer $right): int {
                    $leftTotal =
                        $left->completed_reservations_count +
                        $left->completed_dining_sessions_count +
                        $left->completed_fulfillment_orders_count;
                    $rightTotal =
                        $right->completed_reservations_count +
                        $right->completed_dining_sessions_count +
                        $right->completed_fulfillment_orders_count;

                    return $rightTotal <=> $leftTotal ?:
                        $right->created_at <=> $left->created_at ?:
                        $right->id <=> $left->id;
                })
                ->values();
            $target = $ranked->first();
            $total =
                $target->completed_reservations_count +
                $target->completed_dining_sessions_count +
                $target->completed_fulfillment_orders_count;
            $this->line(
                "{$phone}: giữ #{$target->id} {$target->name} ({$total} đơn hoàn thành), gộp ".
                    ($ranked->count() - 1).
                    ' hồ sơ.',
            );

            if ($this->option('apply')) {
                foreach ($ranked->slice(1) as $source) {
                    $target = $merger->merge($target, $source, true);
                }
            }
        }

        $this->info(
            $this->option('apply') ? 'Đã gộp xong dữ liệu trùng.' : 'Đây là bản xem trước; dùng --apply để thực hiện.',
        );

        return self::SUCCESS;
    }
}
