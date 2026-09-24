<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('roles')->where('code', 'admin')->value('id');
        $managerId = DB::table('roles')->where('code', 'manager')->value('id');

        if ($adminId) {
            DB::table('roles')->where('id', $adminId)->update(['name' => 'Quản trị viên']);
        }
        if ($adminId && $managerId) {
            DB::table('users')->where('role_id', $managerId)->update(['role_id' => $adminId]);
            DB::table('role_permissions')->where('role_id', $managerId)->delete();
            DB::table('roles')->where('id', $managerId)->delete();
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('code', 'admin')->update(['name' => 'Admin']);
    }
};
