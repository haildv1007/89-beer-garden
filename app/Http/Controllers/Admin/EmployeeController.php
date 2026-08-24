<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
        $status = in_array($request->query('status'), array_column(EmployeeStatus::cases(), 'value'), true)
            ? $request->query('status') : '';
        $role = in_array($request->query('role'), Role::CANONICAL_CODES, true) ? $request->query('role') : '';

        $employees = Employee::query()->with('user.role')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$search}%"));
            }))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($role !== '', fn (Builder $query) => $query->whereHas('user.role', fn (Builder $roleQuery) => $roleQuery->where('code', $role)))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.employees.index', [
            'employees' => $employees,
            'roles' => Role::query()->employee()->orderBy('name')->get(),
            'filters' => compact('search', 'status', 'role'),
        ]);
    }

    public function create(): View
    {
        return view('admin.employees.create', ['employee' => new Employee]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::query()->forceCreate($request->validated() + ['status' => EmployeeStatus::Active]);

        return redirect()->route('admin.employees.show', $employee)->with('success', __('app.saved'));
    }

    public function show(Employee $employee): View
    {
        $employee->load('user.role');

        return view('admin.employees.show', [
            'employee' => $employee,
            'roles' => Role::query()->employee()->orderBy('name')->get(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('admin.employees.edit', compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('admin.employees.show', $employee)->with('success', __('app.saved'));
    }
}
