<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('fulfillment_type');
            $table->string('status');
            $table->string('customer_name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->timestamp('requested_for');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('shipping_fee')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->timestamp('placed_at');
            $table->timestamps();
            $table->index(['status', 'requested_for']);
            $table->index(['customer_id', 'placed_at']);
        });

        Schema::create('fulfillment_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_order_items');
        Schema::dropIfExists('fulfillment_orders');
    }
};
