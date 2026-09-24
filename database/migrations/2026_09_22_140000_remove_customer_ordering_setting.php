<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->where('key', 'customer_ordering_enabled')->delete();
    }

    public function down(): void
    {
        // The retired table-link ordering flow is intentionally not restored.
    }
};
