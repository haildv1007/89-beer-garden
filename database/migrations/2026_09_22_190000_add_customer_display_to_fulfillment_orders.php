<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->foreignId('displayed_by_user_id')->nullable()->after('payment_expires_at')
                ->constrained('users', null, 'fulfillment_display_user_fk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->dropForeign('fulfillment_display_user_fk');
            $table->dropColumn('displayed_by_user_id');
        });
    }
};
