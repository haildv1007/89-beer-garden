<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DisableEmployeeRequest;
use App\Models\Employee;
use App\Services\Employee\DisableEmployeeService;
use Illuminate\Http\RedirectResponse;

class EmployeeStatusController extends Controller
{
    public function destroy(DisableEmployeeRequest $request, Employee $employee, DisableEmployeeService $service): RedirectResponse
    {
        $service->disable($employee, $request->user());

        return redirect()->route('admin.employees.show', $employee)->with('success', __('employee.employees.disabled_success'));
    }
}
