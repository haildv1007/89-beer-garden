<?php

namespace App\Services\CustomerOrder;

use App\Models\SystemSetting;

class CustomerOrderingCapability
{
    public const KEY = 'customer_ordering_enabled';

    public function enabled(bool $lockForUpdate = false): bool
    {
        $setting = SystemSetting::query()->where('key', self::KEY)
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())->first();

        return $setting?->type === 'boolean' && $setting->value === 'true';
    }
}
