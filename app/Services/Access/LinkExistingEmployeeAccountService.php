<?php

namespace App\Services\Access;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LinkExistingEmployeeAccountService
{
    public function link(Employee $employee, string $email, Role $role): User
    {
        return DB::transaction(function () use ($employee, $email, $role): User {
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

            $user = User::query()->where('email', mb_strtolower(trim($email)))->lockForUpdate()->firstOrFail();
            $customerRoleId = Role::query()->where('code', 'customer')->value('id');
            if ((int) $user->role_id !== (int) $customerRoleId) {
                throw ValidationException::withMessages([
                    'existing_email' => __('employee.accounts.only_customer_linkable'),
                ]);
            }
            if ($user->employee()->exists()) {
                throw ValidationException::withMessages([
                    'existing_email' => __('employee.accounts.account_already_employee'),
                ]);
            }
            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    'existing_email' => __('employee.accounts.account_disabled'),
                ]);
            }

            $user->forceFill(['role_id' => $role->id])->save();
            $employee->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
