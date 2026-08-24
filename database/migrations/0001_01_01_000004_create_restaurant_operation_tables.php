<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('capacity');
            $table->string('runtime_status');
            $table->boolean('is_active');
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['runtime_status', 'is_active']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->restrictOnDelete();
            $table->string('reservation_code')->unique();
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->unsignedInteger('party_size');
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('confirmed_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['reservation_date', 'reservation_time', 'status']);
            $table->index(['table_id', 'reservation_date', 'reservation_time', 'status'], 'reservations_table_schedule_status_index');
        });

        Schema::create('dining_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique();
            $table->foreignId('table_id')->constrained('restaurant_tables')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('opened_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('completed_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('guest_count');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['table_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('dining_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->foreignId('created_by_customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->string('source');
            $table->text('note')->nullable();
            $table->timestamp('ordered_at');
            $table->timestamps();
            $table->index(['dining_session_id', 'ordered_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('cancelled_by_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        $this->mysqlCheck('restaurant_tables', 'chk_restaurant_tables_capacity_positive', 'capacity > 0');
        $this->mysqlCheck('reservations', 'chk_reservations_party_size_positive', 'party_size > 0');
        $this->mysqlCheck('dining_sessions', 'chk_dining_sessions_guest_count_positive', 'guest_count > 0');
        $this->mysqlCheck('dining_sessions', 'chk_dining_sessions_temporal', 'ended_at IS NULL OR ended_at >= started_at');
        $this->mysqlCheck('orders', 'chk_orders_source', "source IN ('staff','customer')");
        $this->mysqlCheck('order_items', 'chk_order_items_quantity_positive', 'quantity > 0');
        $this->mysqlCheck('order_items', 'chk_order_items_money_non_negative', 'unit_price >= 0 AND line_total >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('dining_sessions');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('customers');
    }

    private function mysqlCheck(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
        }
    }
};
