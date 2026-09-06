<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('unit');
            $table->unsignedBigInteger('current_stock');
            $table->unsignedBigInteger('minimum_stock');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('stock_before');
            $table->unsignedBigInteger('stock_after');
            $table->text('note')->nullable();
            $table->foreignId('created_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['inventory_item_id', 'created_at']);
        });

        $this->mysqlCheck(
            'inventory_items',
            'chk_inventory_stock_non_negative',
            'current_stock >= 0 AND minimum_stock >= 0',
        );
        $this->mysqlCheck('stock_movements', 'chk_stock_movements_quantity_positive', 'quantity > 0');
        $this->mysqlCheck(
            'stock_movements',
            'chk_stock_movements_stock_non_negative',
            'stock_before >= 0 AND stock_after >= 0',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
    }

    private function mysqlCheck(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
        }
    }
};
