<?php

namespace App\Services\SystemSetting;

use App\Models\SystemSetting;

class TypedSystemSettingResolver
{
    public function __construct(private readonly SystemSettingCatalog $catalog) {}

    public function noShowTimeoutMinutes(bool $lockForUpdate = false): ?int
    {
        $value = $this->canonical(SystemSettingCatalog::NO_SHOW_TIMEOUT, $lockForUpdate);

        return $value === null ? null : (int) $value;
    }

    public function customerOrderingEnabled(bool $lockForUpdate = false): bool
    {
        return $this->canonical(SystemSettingCatalog::CUSTOMER_ORDERING, $lockForUpdate) === 'true';
    }

    private function canonical(string $key, bool $lockForUpdate): ?string
    {
        $setting = SystemSetting::query()->where('key', $key)
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())->first();

        if ($setting === null || ! $this->catalog->validPersisted($key, $setting->type, $setting->value)) {
            return null;
        }

        return $setting->value;
    }
}
