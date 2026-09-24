<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('deduplication_key')->unique();
            $table->json('payload');
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->unsignedInteger('print_attempts')->default(0);
            $table->timestamp('printed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });

        $this->backfillDiningOrders();
        $this->backfillFulfillmentOrders();
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_tickets');
    }

    private function backfillDiningOrders(): void
    {
        DB::table('orders')
            ->orderBy('id')
            ->get()
            ->each(function (object $order): void {
                $session = DB::table('dining_sessions')->where('id', $order->dining_session_id)->first();
                $table = $session ? DB::table('restaurant_tables')->where('id', $session->table_id)->first() : null;
                $employee = $order->created_by_employee_id
                    ? DB::table('employees')->where('id', $order->created_by_employee_id)->first()
                    : null;
                $items = DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('id')
                    ->get()
                    ->map(
                        fn (object $item): array => [
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'note' => $item->note,
                        ],
                    )
                    ->all();
                if ($items === []) {
                    return;
                }
                $this->insertTicket(
                    'dining_order',
                    $order->id,
                    'dining-order:'.$order->id,
                    $order->created_by_employee_id,
                    [
                        'source_label' => 'Tại bàn',
                        'location' => $table?->name ?? 'Tại bàn',
                        'table_code' => $table?->code,
                        'session_code' => $session?->session_code,
                        'order_code' => $order->order_code,
                        'ordered_at' => $order->ordered_at,
                        'order_note' => $order->note,
                        'employee_name' => $employee?->name,
                        'items' => $items,
                    ],
                    $order->created_at,
                );
            });
    }

    private function backfillFulfillmentOrders(): void
    {
        DB::table('fulfillment_orders')
            ->where('status', 'confirmed')
            ->whereIn('fulfillment_type', ['pickup', 'delivery'])
            ->orderBy('id')
            ->get()
            ->each(function (object $order): void {
                $employee = $order->confirmed_by_employee_id
                    ? DB::table('employees')->where('id', $order->confirmed_by_employee_id)->first()
                    : null;
                $items = DB::table('fulfillment_order_items')
                    ->where('fulfillment_order_id', $order->id)
                    ->orderBy('id')
                    ->get()
                    ->map(
                        fn (object $item): array => [
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'note' => $item->note,
                        ],
                    )
                    ->all();
                if ($items === []) {
                    return;
                }
                $label = $order->fulfillment_type === 'delivery' ? 'Giao hàng' : 'Mang về';
                $this->insertTicket(
                    'fulfillment_order',
                    $order->id,
                    'fulfillment-order:'.$order->id,
                    $order->confirmed_by_employee_id,
                    [
                        'source_label' => $label,
                        'location' => $label,
                        'order_code' => $order->order_code,
                        'ordered_at' => $order->placed_at,
                        'requested_for' => $order->requested_for,
                        'customer_name' => $order->customer_name,
                        'phone' => $order->phone,
                        'order_note' => $order->note,
                        'employee_name' => $employee?->name,
                        'items' => $items,
                    ],
                    $order->confirmed_at ?? $order->created_at,
                );
            });
    }

    private function insertTicket(
        string $sourceType,
        int $sourceId,
        string $key,
        ?int $employeeId,
        array $payload,
        string $createdAt,
    ): void {
        DB::table('kitchen_tickets')->insert([
            'ticket_code' => 'KOT-'.Str::upper(Str::random(8)),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'type' => 'order',
            'status' => 'printed',
            'deduplication_key' => $key,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_by_employee_id' => $employeeId,
            'print_attempts' => 1,
            'printed_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
};
