<?php

namespace App\Services\AIChat;

use App\Services\SystemSetting\TypedSystemSettingResolver;

class RestaurantContextBuilder
{
    public function __construct(private readonly TypedSystemSettingResolver $settings) {}

    /** @return array{address:?string,phone:?string,email:?string,facebook_url:?string,zalo_url:?string,opening_hours:?string,map_url:?string} */
    public function build(): array
    {
        return $this->settings->contactInformation();
    }

    public function deliveryFee(): ?int
    {
        return $this->settings->deliveryFee();
    }

    public function vietQrAvailable(): bool
    {
        return $this->settings->vietQr() !== null;
    }
}
