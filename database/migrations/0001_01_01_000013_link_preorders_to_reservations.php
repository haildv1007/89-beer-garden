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
                ->foreignId('reservation_id')
                ->nullable()
                ->unique()
                ->after('customer_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fulfillment_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('reservation_id'));
    }
};
