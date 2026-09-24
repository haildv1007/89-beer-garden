<?php

use App\Models\FulfillmentOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('fulfillment_orders')
                ->join('dining_sessions', 'dining_sessions.reservation_id', '=', 'fulfillment_orders.reservation_id')
                ->where('fulfillment_orders.fulfillment_type', FulfillmentOrder::TYPE_DINE_IN)
                ->where('fulfillment_orders.status', '!=', FulfillmentOrder::STATUS_REJECTED)
                ->select([
                    'fulfillment_orders.id as preorder_id',
                    'fulfillment_orders.order_code',
                    'fulfillment_orders.note',
                    'fulfillment_orders.confirmed_at',
                    'dining_sessions.id as session_id',
                    'dining_sessions.customer_id',
                    'dining_sessions.opened_by_employee_id',
                    'dining_sessions.started_at',
                ])
                ->orderBy('fulfillment_orders.id')
                ->each(function ($preorder): void {
                    if (DB::table('orders')->where('order_code', $preorder->order_code)->exists()) {
                        return;
                    }

                    $now = now();
                    $orderId = DB::table('orders')->insertGetId([
                        'order_code' => $preorder->order_code,
                        'dining_session_id' => $preorder->session_id,
                        'created_by_employee_id' => $preorder->opened_by_employee_id,
                        'created_by_customer_id' => null,
                        'source' => 'customer',
                        'note' => $preorder->note,
                        'ordered_at' => $preorder->started_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('fulfillment_order_items')
                        ->where('fulfillment_order_id', $preorder->preorder_id)
                        ->orderBy('id')
                        ->each(function ($item) use ($orderId, $now): void {
                            DB::table('order_items')->insert([
                                'order_id' => $orderId,
                                'product_id' => $item->product_id,
                                'product_name' => $item->product_name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'line_total' => $item->line_total,
                                'status' => $item->status,
                                'note' => $item->note,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        });

                    DB::table('fulfillment_orders')
                        ->where('id', $preorder->preorder_id)
                        ->update([
                            'status' => FulfillmentOrder::STATUS_CONFIRMED,
                            'confirmed_by_employee_id' => $preorder->opened_by_employee_id,
                            'confirmed_at' => $preorder->confirmed_at ?? $preorder->started_at,
                            'updated_at' => $now,
                        ]);
                });
        });
    }

    public function down(): void
    {
        // The generated service orders may already have entered kitchen or billing workflows.
    }
};
