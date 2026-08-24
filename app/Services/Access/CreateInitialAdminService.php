<?php

namespace App\Services\Access;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInitialAdminService
{
    public function create(string $employeeCode, string $employeeName, string $email, string $password): User
    {
        return DB::transaction(function () use ($employeeCode, $employeeName, $email, $password): User {
            $role = Role::query()->where('code', 'admin')->lockForUpdate()->first();

            if (! $role) {
                throw ValidationException::withMessages(['role' => __('employee.bootstrap_admin.role_missing')]);
            }

            $hasActiveAdmin = User::query()
                ->select('users.id')
                ->join('employees', 'employees.user_id', '=', 'users.id')
                ->where('users.role_id', $role->id)
                ->where('users.status', User::STATUS_ACTIVE)
                ->where('employees.status', EmployeeStatus::Active->value)
                ->whereNull('employees.deleted_at')
                ->lockForUpdate()
                ->first() !== null;

            if ($hasActiveAdmin) {
                throw ValidationException::withMessages(['admin' => __('employee.bootstrap_admin.already_exists')]);
            }

            $user = User::query()->forceCreate([
                'email' => $email,
                'password' => $password,
                'role_id' => $role->id,
                'status' => User::STATUS_ACTIVE,
            ]);

            Employee::query()->forceCreate([
                'user_id' => $user->id,
                'employee_code' => $employeeCode,
                'name' => $employeeName,
                'status' => EmployeeStatus::Active,
            ]);

            return $user;
        });
    }
}
