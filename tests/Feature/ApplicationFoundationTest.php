<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationFoundationTest extends TestCase
{
    public function test_each_presentation_context_has_a_working_entry_point(): void
    {
        $entryPoints = [
            'customer.home' => 'Khách hàng',
            'pos.home' => 'Điểm bán hàng',
            'kitchen.home' => 'Bếp',
            'admin.home' => 'Quản trị',
        ];

        foreach ($entryPoints as $route => $translatedContext) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($translatedContext);
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
