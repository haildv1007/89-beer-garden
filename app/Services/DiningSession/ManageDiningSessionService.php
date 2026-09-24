<?php

namespace App\Services\DiningSession;

use App\Enums\OrderItemStatus;
use App\Models\DiningSession;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Order\CreateOrderService;
use App\Services\Order\UpdateWaitingOrderItemService;
use App\Services\OrderItem\CancelOrderItemService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageDiningSessionService
{
    public function __construct(
        private readonly UpdateDiningSessionService $sessions,
        private readonly UpdateWaitingOrderItemService $items,
        private readonly CancelOrderItemService $cancellations,
        private readonly CreateOrderService $orders,
    ) {}

    /** @param array<string, mixed> $data */
    public function manage(DiningSession $session, User $actor, array $data): void
    {
        DB::transaction(function () use ($session, $actor, $data): void {
            $this->sessions->update($session, [
                'customer_name' => $data['customer_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'guest_count' => $data['guest_count'],
                'note' => $data['session_note'] ?? null,
            ]);

            if (! empty($data['new_items'])) {
                abort_unless($actor->can('order.create'), 403);
                $this->orders->create($session, $actor, array_values($data['new_items']), $data['order_note'] ?? null);
            }

            foreach ($data['existing_items'] ?? [] as $itemId => $input) {
                $item = OrderItem::query()
                    ->whereKey($itemId)
                    ->whereHas('order', fn ($query) => $query->where('dining_session_id', $session->id))
                    ->first();
                if (
                    $item === null ||
                    ! in_array($item->status, [OrderItemStatus::Waiting, OrderItemStatus::Preparing], true)
                ) {
                    throw ValidationException::withMessages([
                        'existing_items' => 'Có món không còn ở trạng thái cho phép chỉnh sửa.',
                    ]);
                }

                if ((bool) ($input['cancel'] ?? false)) {
                    $permission =
                        $item->status === OrderItemStatus::Waiting
                            ? 'order-item.cancel-waiting'
                            : 'order-item.cancel-preparing';
                    abort_unless($actor->can($permission), 403);
                    $reason = trim((string) ($input['cancellation_reason'] ?? ''));
                    $item->status === OrderItemStatus::Waiting
                        ? $this->cancellations->cancelWaiting($item, $actor, $reason)
                        : $this->cancellations->cancelPreparing($item, $actor, $reason);
                } elseif ($item->status === OrderItemStatus::Waiting) {
                    $this->items->update($item, $actor, (int) $input['quantity'], $input['note'] ?? null);
                }
            }
        });
    }
}
