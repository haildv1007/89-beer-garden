<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncRolePermissionsService
{
    /** @param list<int> $permissionIds */
    public function sync(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds): void {
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);

            if ($role->code === 'admin') {
                $mandatoryIds = Permission::query()
                    ->whereIn('code', ['context.admin.access', 'permission.assign'])
                    ->lockForUpdate()
                    ->pluck('id');

                if ($mandatoryIds->count() !== 2 || $mandatoryIds->diff($permissionIds)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'permissions' => __('employee.roles.admin_mandatory_permissions'),
                    ]);
                }
            }

            $role->permissions()->sync($permissionIds);
        });
    }
}
