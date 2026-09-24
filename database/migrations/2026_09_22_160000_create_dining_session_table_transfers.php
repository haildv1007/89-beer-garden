<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_session_table_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dining_session_id')->constrained(null, null, 'dst_session_fk')->restrictOnDelete();
            $table->foreignId('from_table_id')->constrained('restaurant_tables', null, 'dst_from_table_fk')->restrictOnDelete();
            $table->foreignId('to_table_id')->constrained('restaurant_tables', null, 'dst_to_table_fk')->restrictOnDelete();
            $table->foreignId('transferred_by_employee_id')->constrained('employees', null, 'dst_employee_fk')->restrictOnDelete();
            $table->text('reason');
            $table->timestamp('transferred_at');
            $table->timestamps();
            $table->index(['dining_session_id', 'transferred_at'], 'dst_session_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_session_table_transfers');
    }
};
