<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('payment_reference', 40)->nullable()->unique()->after('status');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_reference');
            $table->foreignId('displayed_by_user_id')->nullable()->after('payment_expires_at')
                ->constrained('users', null, 'bills_display_user_fk')->nullOnDelete();
        });
        Schema::table('payment_webhook_transactions', function (Blueprint $table) {
            $table->foreignId('bill_id')->nullable()->after('fulfillment_order_id')
                ->constrained('bills', null, 'pwt_bill_fk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_webhook_transactions', function (Blueprint $table) {
            $table->dropForeign('pwt_bill_fk');
            $table->dropColumn('bill_id');
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign('bills_display_user_fk');
            $table->dropColumn(['displayed_by_user_id', 'payment_reference', 'payment_expires_at']);
        });
    }
};
