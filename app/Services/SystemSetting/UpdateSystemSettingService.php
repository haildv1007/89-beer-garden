<?php

namespace App\Services\SystemSetting;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSystemSettingService
{
    public function __construct(private readonly SystemSettingCatalog $catalog) {}

    public function update(string $key, mixed $value, User $actor): SystemSetting
    {
        $definition = $this->catalog->definition($key);
        $canonical = $this->catalog->canonicalValue($key, $value);
        if ($definition === null || $canonical === null) {
            throw ValidationException::withMessages(['value' => __('setting.validation.invalid')]);
        }

        try {
            return $this->persist($key, $canonical, $definition['type'], $actor);
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            return $this->persist($key, $canonical, $definition['type'], $actor);
        }
    }

    private function persist(string $key, string $value, string $type, User $actor): SystemSetting
    {
        return DB::transaction(function () use ($key, $value, $type, $actor): SystemSetting {
            $setting = SystemSetting::query()->where('key', $key)->lockForUpdate()->first();
            $employee = Employee::query()->where('user_id', $actor->id)->lockForUpdate()->first();
            $lockedUser = User::query()->lockForUpdate()->find($actor->id);

            if ($lockedUser === null || ! $lockedUser->isActive()
                || $employee === null || $employee->status !== EmployeeStatus::Active) {
                throw ValidationException::withMessages(['value' => __('setting.validation.actor_inactive')]);
            }

            $setting ??= new SystemSetting;
            $setting->forceFill(['key' => $key, 'value' => $value, 'type' => $type,
                'updated_by_employee_id' => $employee->id])->save();

            return $setting->fresh(['updatedBy:id,name']);
        }, 3);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true);
    }
}
