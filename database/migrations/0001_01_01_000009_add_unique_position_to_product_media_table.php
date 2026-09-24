<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_media', function (Blueprint $table): void {
            $table->unique(['product_id', 'sort_order'], 'product_media_product_position_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_media', function (Blueprint $table): void {
            $table->dropUnique('product_media_product_position_unique');
        });
    }
};
