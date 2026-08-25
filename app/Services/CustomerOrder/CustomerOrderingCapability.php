<?php

namespace App\Services\CustomerOrder;

use App\Services\SystemSetting\TypedSystemSettingResolver;

class CustomerOrderingCapability
{
    public const KEY = 'customer_ordering_enabled';

    public function __construct(private readonly TypedSystemSettingResolver $settings) {}

    public function enabled(bool $lockForUpdate = false): bool
    {
        return $this->settings->customerOrderingEnabled($lockForUpdate);
    }
}
