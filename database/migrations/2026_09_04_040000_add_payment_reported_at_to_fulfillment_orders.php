<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'fulfillment_orders',
            fn (Blueprint $table) => $table->timestamp('payment_reported_at')->nullable()->after('payment_status'),
        );
    }

    public function down(): void
    {
        Schema::table('fulfillment_orders', fn (Blueprint $table) => $table->dropColumn('payment_reported_at'));
    }
};
