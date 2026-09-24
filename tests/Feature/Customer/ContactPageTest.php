<?php

namespace Tests\Feature\Customer;

use App\Models\SystemSetting;
use App\Services\SystemSetting\SystemSettingCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_displays_valid_public_store_information_and_footer_link(): void
    {
        $values = [
            SystemSettingCatalog::CONTACT_ADDRESS => '89 Đường Vườn Bia, Quận 1, TP. Hồ Chí Minh',
            SystemSettingCatalog::CONTACT_PHONE => '+84 28 1234 5678',
            SystemSettingCatalog::CONTACT_EMAIL => 'hello@89beergarden.vn',
            SystemSettingCatalog::OPENING_HOURS => "Thứ Hai–Thứ Sáu: 16:00–23:00\nThứ Bảy–Chủ Nhật: 10:00–23:30",
            SystemSettingCatalog::MAP_URL => 'https://maps.google.com/?q=89+Beer+Garden',
        ];
        foreach ($values as $key => $value) {
            SystemSetting::query()->forceCreate(['key' => $key, 'value' => $value, 'type' => 'string']);
        }

        $this->get(route('customer.contact'))
            ->assertOk()
            ->assertSee(__('customer_ui.contact_title'))
            ->assertSee($values[SystemSettingCatalog::CONTACT_ADDRESS])
            ->assertSee('href="tel:+842812345678"', false)
            ->assertSee('href="mailto:hello@89beergarden.vn"', false)
            ->assertSee('Thứ Hai–Thứ Sáu: 16:00–23:00')
            ->assertSee('href="https://maps.google.com/?q=89+Beer+Garden"', false);

        $this->get(route('customer.home'))
            ->assertOk()
            ->assertSee(route('customer.contact'))
            ->assertSee(__('customer_ui.contact_navigation'));
    }

    public function test_contact_page_has_a_safe_empty_state_when_admin_has_not_configured_information(): void
    {
        $this->get(route('customer.contact'))
            ->assertOk()
            ->assertSee(__('customer_ui.contact_pending_title'))
            ->assertSee(route('customer.reservations.create'));
    }

    public function test_invalid_persisted_contact_values_are_not_exposed(): void
    {
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::MAP_URL,
            'value' => 'javascript:alert(1)',
            'type' => 'string',
        ]);

        $this->get(route('customer.contact'))
            ->assertOk()
            ->assertDontSee('javascript:alert(1)', false)
            ->assertSee(__('customer_ui.contact_pending_title'));
    }
}
