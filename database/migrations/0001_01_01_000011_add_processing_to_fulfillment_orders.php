<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table
                ->foreignId('confirmed_by_employee_id')
                ->nullable()
                ->after('status')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by_employee_id');
            $table->timestamp('rejected_at')->nullable()->after('confirmed_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });
        Schema::table('fulfillment_order_items', function (Blueprint $table) {
            $table->string('status')->default('waiting')->after('note')->index();
        });
    }

    public function down(): void
    {
        Schema::table('fulfillment_order_items', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by_employee_id');
            $table->dropColumn(['confirmed_at', 'rejected_at', 'rejection_reason']);
        });
    }
};
