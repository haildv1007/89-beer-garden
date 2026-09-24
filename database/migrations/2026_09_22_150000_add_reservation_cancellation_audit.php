<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('cancelled_by_employee_id')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancelled_by_employee_id');
            $table->dropColumn('cancellation_reason');
        });
    }
};
