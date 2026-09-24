<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->string('image_url')->nullable();
            $table->string('status');
            $table->boolean('is_available');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'status']);
        });

        $this->mysqlCheck('products', 'chk_products_price_non_negative', 'price >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }

    private function mysqlCheck(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
        }
    }
};
