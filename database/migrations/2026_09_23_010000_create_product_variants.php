<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'is_available', 'sort_order']);
        });

        foreach (['order_items', 'fulfillment_order_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
                $table->string('variant_name')->nullable()->after('product_name');
            });
        }
    }

    public function down(): void
    {
        foreach (['fulfillment_order_items', 'order_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_variant_id');
                $table->dropColumn('variant_name');
            });
        }
        Schema::dropIfExists('product_variants');
    }
};
