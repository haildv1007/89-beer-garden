<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->string('payment_option')->default('pay_on_receipt')->after('fulfillment_type');
            $table->string('payment_status')->default('unpaid')->after('total_amount')->index();
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->unsignedBigInteger('received_amount')->nullable()->after('payment_method');
            $table->unsignedBigInteger('change_amount')->nullable()->after('received_amount');
            $table->timestamp('paid_at')->nullable()->after('change_amount');
            $table
                ->foreignId('paid_by_employee_id')
                ->nullable()
                ->after('paid_at')
                ->constrained('employees')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by_employee_id');
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'payment_option',
                'payment_status',
                'payment_method',
                'received_amount',
                'change_amount',
                'paid_at',
            ]);
        });
    }
};
