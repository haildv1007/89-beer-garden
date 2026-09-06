<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('translations')->where('source', 'manual')->delete();

            $permissionIds = DB::table('permissions')->where('code', 'translation.update')->pluck('id');

            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        });
    }

    public function down(): void
    {
        // Removed manual translations cannot be reconstructed safely.
    }
};
