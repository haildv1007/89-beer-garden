<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->string('payment_reference', 40)->nullable()->unique()->after('payment_option');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_reference');
        });

        Schema::create('payment_webhook_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('external_transaction_id', 100);
            $table->foreignId('fulfillment_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30);
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('reference_code', 150)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->text('content')->nullable();
            $table->json('payload');
            $table->timestamps();
            $table->unique(['provider', 'external_transaction_id'], 'payment_webhook_provider_tx_unique');
            $table->index(['provider', 'status', 'created_at'], 'payment_webhook_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_transactions');
        Schema::table('fulfillment_orders', fn (Blueprint $table) => $table->dropColumn(['payment_reference', 'payment_expires_at']));
    }
};
