<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dining_sessions', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('ended_at');
            $table->foreignId('cancelled_by_employee_id')->nullable()->after('completed_by_employee_id')
                ->constrained('employees', null, 'ds_cancelled_by_fk')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('dining_sessions', function (Blueprint $table) {
            $table->dropForeign('ds_cancelled_by_fk');
            $table->dropColumn(['cancelled_by_employee_id', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
