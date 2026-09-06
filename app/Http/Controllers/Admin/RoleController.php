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
        // Eager-loading passes a relation instance here, not always an Eloquent Builder.
        $approvedPermissions = static fn ($query) => $query->approved();

        return view('admin.roles.index', [
            'roles' => Role::query()
                ->canonical()
                ->with(['permissions' => $approvedPermissions])
                ->withCount([
                    'users',
                    'permissions' => $approvedPermissions,
                ])
                ->orderByRaw(
                    "case code when 'admin' then 1 when 'manager' then 2 when 'staff' then 3 when 'kitchen' then 4 else 5 end",
                )
                ->get(),
            'permissions' => Permission::query()->approved()->orderBy('code')->get(),
        ]);
    }

    public function updatePermissions(
        SyncRolePermissionsRequest $request,
        Role $role,
        SyncRolePermissionsService $service,
    ): RedirectResponse {
        abort_unless(in_array($role->code, Role::CANONICAL_CODES, true), 404);
        $service->sync($role, $request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', __('employee.roles.permissions_updated'));
    }
}
