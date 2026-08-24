<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeAccountRequest;
use App\Http\Requests\Admin\UpdateEmployeeRoleRequest;
use App\Models\Employee;
use App\Models\Role;
use App\Services\Access\CreateEmployeeAccountService;
use Illuminate\Http\RedirectResponse;

class EmployeeAccountController extends Controller
{
    public function store(StoreEmployeeAccountRequest $request, Employee $employee, CreateEmployeeAccountService $service): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::query()->employee()->findOrFail($data['role_id']);
        $service->create($employee, $data['email'], $data['password'], $role);

        return redirect()->route('admin.employees.show', $employee)->with('success', __('employee.accounts.created'));
    }

    public function update(UpdateEmployeeRoleRequest $request, Employee $employee): RedirectResponse
    {
        abort_if($employee->user_id === null, 404);
        $role = Role::query()->employee()->findOrFail($request->integer('role_id'));
        $employee->user->forceFill(['role_id' => $role->id])->save();

        return redirect()->route('admin.employees.show', $employee)->with('success', __('employee.accounts.role_updated'));
    }
}
