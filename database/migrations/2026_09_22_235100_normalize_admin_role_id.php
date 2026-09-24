<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $admin = DB::table('roles')->where('code', 'admin')->first();

            if ($admin === null || (int) $admin->id === 4) {
                return;
            }

            if (DB::table('roles')->where('id', 4)->exists()) {
                throw new \RuntimeException('Cannot normalize the admin role because role ID 4 is already in use.');
            }

            DB::table('roles')->where('id', $admin->id)->update([
                'code' => 'admin_legacy_'.$admin->id,
            ]);

            DB::table('roles')->insert([
                'id' => 4,
                'code' => $admin->code,
                'name' => $admin->name,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]);

            DB::table('users')->where('role_id', $admin->id)->update(['role_id' => 4]);
            DB::table('role_permissions')->where('role_id', $admin->id)->update(['role_id' => 4]);
            DB::table('roles')->where('id', $admin->id)->delete();
        });
    }

    public function down(): void
    {
        // Role IDs are normalized data and should not be changed back during rollback.
    }
};
