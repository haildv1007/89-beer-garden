<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSetting\SystemSettingCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_widget_is_server_rendered_for_guests_only_when_feature_is_enabled(): void
    {
        config(['features.ai_chat' => true]);
        foreach ([
            SystemSettingCatalog::CONTACT_PHONE => '+84 90 123 4567',
            SystemSettingCatalog::CONTACT_FACEBOOK_URL => 'https://m.me/89beergarden',
            SystemSettingCatalog::CONTACT_ZALO_URL => 'https://zalo.me/84901234567',
        ] as $key => $value) {
            SystemSetting::query()->forceCreate(['key' => $key, 'value' => $value, 'type' => 'string']);
        }

        $this->get(route('customer.home'))
            ->assertOk()
            ->assertSee('data-ai-chat-widget', false)
            ->assertSee('ai-chat\\/state', false)
            ->assertSee('ai-chat\\/messages', false)
            ->assertSee('ai-chat\\/reset', false)
            ->assertSee('data-ai-chat-tab="chat"', false)
            ->assertSee('data-ai-chat-tab="support"', false)
            ->assertSee('ai-chat__title-status', false)
            ->assertDontSee('class="ai-chat__status"', false)
            ->assertSee('https://m.me/89beergarden', false)
            ->assertSee('https://zalo.me/84901234567', false)
            ->assertSee('tel:+84901234567', false)
            ->assertSee('meta name="csrf-token"', false);

        config(['features.ai_chat' => false]);
        $this->get(route('customer.home'))
            ->assertOk()
            ->assertDontSee('data-ai-chat-widget', false)
            ->assertDontSee('ai-chat\\/state', false);
    }

    public function test_logged_in_customer_can_render_widget_and_staff_layouts_do_not_include_it(): void
    {
        config(['features.ai_chat' => true]);
        $user = User::factory()
            ->forRole(Role::query()->where('code', 'customer')->firstOrFail())
            ->create();
        Customer::query()->forceCreate([
            'user_id' => $user->id,
            'name' => 'Widget Customer',
            'email' => 'widget@example.test',
        ]);

        $this->actingAs($user)->get(route('customer.home'))->assertOk()->assertSee('data-ai-chat-widget', false);

        foreach (['admin', 'pos', 'kitchen'] as $layout) {
            $contents = file_get_contents(resource_path("views/layouts/{$layout}.blade.php"));
            $this->assertStringNotContainsString('ai-chat-widget', $contents);
        }
    }

    public function test_database_gemini_switch_overrides_environment_feature_flag(): void
    {
        config(['features.ai_chat' => false]);
        $setting = SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::GEMINI_ENABLED,
            'value' => '1',
            'type' => 'boolean',
        ]);

        $this->get(route('customer.home'))->assertOk()->assertSee('data-ai-chat-widget', false);

        $setting->forceFill(['value' => '0'])->save();
        config(['features.ai_chat' => true]);

        $this->get(route('customer.home'))->assertOk()->assertDontSee('data-ai-chat-widget', false);
    }

    public function test_widget_translation_contract_is_complete_for_supported_locales(): void
    {
        $required = [
            'launcher',
            'title',
            'subtitle',
            'ready',
            'tabs_label',
            'chat_tab',
            'support_tab',
            'support_title',
            'support_copy',
            'facebook_copy',
            'zalo_copy',
            'phone_copy',
            'support_empty',
            'support_note',
            'greeting',
            'placeholder',
            'send',
            'close',
            'new_conversation',
            'typing',
            'loading_history',
            'retry',
            'generic_error',
            'offline',
            'rate_limit',
            'handoff_title',
            'add_item',
            'add_set',
            'batch_confirmation',
            'cart_added',
            'set_added',
            'cart_failed',
            'set_failed',
            'open_cart',
            'recommendation_title',
            'empty',
        ];

        foreach (['vi', 'en', 'zh'] as $locale) {
            $translations = require lang_path("{$locale}/ai_chat.php");
            foreach ($required as $key) {
                $this->assertIsString($translations['widget'][$key] ?? null, "Missing {$locale}.ai_chat.widget.{$key}");
                $this->assertNotSame('', trim($translations['widget'][$key]));
            }
        }
    }
}
