<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Access\SyncRolePermissionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->canonical()->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->approved()->orderBy('code')->get(),
        ]);
    }

    public function updatePermissions(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissionsService $service): RedirectResponse
    {
        abort_unless(in_array($role->code, Role::CANONICAL_CODES, true), 404);
        $service->sync($role, $request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', __('employee.roles.permissions_updated'));
    }
}
