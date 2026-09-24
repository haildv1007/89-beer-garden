<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kitchen_tickets')
            ->whereIn('status', ['pending', 'printing'])
            ->update([
                'status' => 'printed',
                'printed_at' => now(),
                'print_attempts' => DB::raw('CASE WHEN print_attempts < 1 THEN 1 ELSE print_attempts END'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Không đưa phiếu lịch sử trở lại hàng đợi vì có thể gây in trùng hàng loạt.
    }
};
