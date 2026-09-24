<?php

namespace App\Services\Access;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEmployeeAccountService
{
    public function create(Employee $employee, string $email, string $password, Role $role): User
    {
        return DB::transaction(function () use ($employee, $email, $password, $role): User {
            if (! in_array($role->code, Role::EMPLOYEE_CODES, true)) {
                throw ValidationException::withMessages(['role_id' => __('employee.accounts.invalid_role')]);
            }

            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);

            if ($employee->user_id !== null) {
                throw ValidationException::withMessages(['employee' => __('employee.accounts.already_linked')]);
            }

            if ($employee->status !== EmployeeStatus::Active) {
                throw ValidationException::withMessages(['employee' => __('employee.accounts.employee_disabled')]);
            }

            $user = User::query()->forceCreate([
                'email' => $email,
                'password' => $password,
                'role_id' => $role->id,
                'status' => User::STATUS_ACTIVE,
            ]);

            $employee->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
