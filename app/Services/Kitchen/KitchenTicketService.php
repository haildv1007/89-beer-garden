<?php

namespace App\Services\Kitchen;

use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\BusinessCode\BusinessCodeGenerator;
use Illuminate\Support\Str;

class KitchenTicketService
{
    public function __construct(private readonly BusinessCodeGenerator $codes) {}

    public function createForOrder(Order $order, ?Employee $employee = null): KitchenTicket
    {
        $order->loadMissing(['items', 'diningSession.table', 'createdByEmployee']);

        return $this->create(
            'dining_order',
            $order->id,
            KitchenTicket::TYPE_ORDER,
            "dining-order:{$order->id}",
            $this->orderPayload($order, $order->items->map(fn (OrderItem $item) => $this->itemPayload($item))->all()),
            $employee?->id ?? $order->created_by_employee_id,
        );
    }

    public function createForFulfillmentOrder(FulfillmentOrder $order, ?Employee $employee = null): KitchenTicket
    {
        $order->loadMissing(['items', 'confirmedByEmployee']);
        $type = $order->fulfillment_type === FulfillmentOrder::TYPE_DELIVERY ? 'Giao hàng' : 'Mang về';

        return $this->create(
            'fulfillment_order',
            $order->id,
            KitchenTicket::TYPE_ORDER,
            "fulfillment-order:{$order->id}",
            [
                'source_label' => $type,
                'location' => $type,
                'order_code' => $order->order_code,
                'ordered_at' => optional($order->placed_at)->toIso8601String(),
                'requested_for' => optional($order->requested_for)->toIso8601String(),
                'customer_name' => $order->customer_name,
                'phone' => $order->phone,
                'order_note' => $order->note,
                'employee_name' => $employee?->name ?? $order->confirmedByEmployee?->name,
                'items' => $order->items->map(fn ($item) => $this->itemPayload($item))->all(),
            ],
            $employee?->id ?? $order->confirmed_by_employee_id,
        );
    }

    public function createAdjustment(OrderItem $item, User $actor, array $before): KitchenTicket
    {
        $item->loadMissing('order.diningSession.table');
        $employee = Employee::query()->where('user_id', $actor->id)->first();
        $payload = $this->orderPayload($item->order, [
            [
                ...$this->itemPayload($item),
                'change' => sprintf(
                    'SL %s → %s; ghi chú: %s → %s',
                    $before['quantity'],
                    $item->quantity,
                    $before['note'] ?: '—',
                    $item->note ?: '—',
                ),
            ],
        ]);

        return $this->create(
            'dining_order',
            $item->order_id,
            KitchenTicket::TYPE_ADJUSTMENT,
            'adjustment:'.$item->id.':'.Str::ulid(),
            $payload,
            $employee?->id,
        );
    }

    public function createCancellation(OrderItem $item, Employee $employee): KitchenTicket
    {
        $item->loadMissing('order.diningSession.table');
        $payload = $this->orderPayload($item->order, [
            [...$this->itemPayload($item), 'change' => 'HỦY MÓN: '.$item->cancellation_reason],
        ]);

        return $this->create(
            'dining_order',
            $item->order_id,
            KitchenTicket::TYPE_CANCELLATION,
            "cancellation:{$item->id}",
            $payload,
            $employee->id,
        );
    }

    private function orderPayload(Order $order, array $items): array
    {
        return [
            'source_label' => 'Tại bàn',
            'location' => $order->diningSession->table->name,
            'table_code' => $order->diningSession->table->code,
            'session_code' => $order->diningSession->session_code,
            'order_code' => $order->order_code,
            'ordered_at' => optional($order->ordered_at)->toIso8601String(),
            'order_note' => $order->note,
            'employee_name' => $order->createdByEmployee?->name,
            'items' => $items,
        ];
    }

    private function itemPayload(object $item): array
    {
        return ['product_name' => $item->product_name, 'quantity' => $item->quantity, 'note' => $item->note];
    }

    private function create(
        string $sourceType,
        int $sourceId,
        string $type,
        string $key,
        array $payload,
        ?int $employeeId,
    ): KitchenTicket {
        return KitchenTicket::query()->firstOrCreate(
            ['deduplication_key' => $key],
            [
                'ticket_code' => $this->codes->next(BusinessCodeGenerator::KITCHEN_TICKET),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'type' => $type,
                'status' => KitchenTicket::STATUS_PENDING,
                'payload' => $payload,
                'created_by_employee_id' => $employeeId,
            ],
        );
    }
}
