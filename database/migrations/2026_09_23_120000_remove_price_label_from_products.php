<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'price_label')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('price_label');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'price_label')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('price_label')->nullable()->after('price');
            });
        }
    }
};
