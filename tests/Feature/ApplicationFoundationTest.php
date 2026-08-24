<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationFoundationTest extends TestCase
{
    public function test_public_and_internal_context_entry_points_follow_the_authentication_boundary(): void
    {
        $this->get(route('customer.home'))
            ->assertOk()
            ->assertSee('Khách hàng');

        $internalEntryPoints = [
            'pos.home' => 'Điểm bán hàng',
            'kitchen.home' => 'Bếp',
            'admin.home' => 'Quản trị',
        ];

        foreach ($internalEntryPoints as $route => $translatedContext) {
            $this->get(route($route))
                ->assertRedirect(route('login'))
                ->assertDontSee($translatedContext);
        }
    }

    public function test_localization_baseline_is_configured(): void
    {
        $this->assertSame('vi', config('app.locale'));
        $this->assertSame('vi', config('app.fallback_locale'));
        $this->assertSame('Asia/Bangkok', config('app.timezone'));
        $this->assertSame(['vi', 'en', 'zh'], config('localization.supported_locales'));

        foreach (config('localization.supported_locales') as $locale) {
            app()->setLocale($locale);

            $this->assertNotSame('app.foundation_ready', __('app.foundation_ready'));
        }
    }
}
