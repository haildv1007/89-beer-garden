<?php

namespace App\Services\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisableEmployeeService
{
    public function disable(Employee $employee, User $actor): void
    {
        DB::transaction(function () use ($employee, $actor): void {
            $adminRole = Role::query()->where('code', 'admin')->lockForUpdate()->firstOrFail();
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $targetUser =
                $employee->user_id === null ? null : User::query()->lockForUpdate()->findOrFail($employee->user_id);

            if ($targetUser?->role_id === $adminRole->id) {
                if ($actor->role_id !== $adminRole->id) {
                    throw new AuthorizationException(__('employee.employees.admin_disable_forbidden'));
                }

                $activeAdminIds = User::query()
                    ->select('users.id')
                    ->join('employees', 'employees.user_id', '=', 'users.id')
                    ->where('users.role_id', $adminRole->id)
                    ->where('users.status', User::STATUS_ACTIVE)
                    ->where('employees.status', EmployeeStatus::Active->value)
                    ->whereNull('employees.deleted_at')
                    ->lockForUpdate()
                    ->pluck('users.id');

                $targetIsActiveAdmin = $activeAdminIds->contains($targetUser->id);

                if ($targetIsActiveAdmin && $activeAdminIds->count() <= 1) {
                    throw ValidationException::withMessages([
                        'employee' => __('employee.employees.last_admin_protected'),
                    ]);
                }
            }

            $employee->forceFill(['status' => EmployeeStatus::Disabled])->save();

            if ($targetUser !== null) {
                $targetUser->forceFill(['status' => User::STATUS_DISABLED])->save();
            }
        });
    }
}
