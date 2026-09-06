<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CustomerOrder\CustomerOrderingCapability;
use App\Services\SystemSetting\SystemSettingCatalog;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use App\Services\SystemSetting\UpdateSystemSettingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class SystemSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_view_create_and_update_only_catalog_settings_with_audit(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['tab' => 'operation']))
            ->assertOk()
            ->assertSee(__('setting.title'))
            ->assertSee('no_show_timeout_minutes')
            ->assertDontSee((string) config('app.key'));
        $this->assertDatabaseCount('system_settings', 0);

        $this->put(route('admin.settings.update', SystemSettingCatalog::NO_SHOW_TIMEOUT), [
            'value' => '30',
        ])->assertRedirect();
        $setting = SystemSetting::query()->sole();
        $this->assertSame(
            ['30', 'integer', $admin->employee->id],
            [$setting->value, $setting->type, $setting->updated_by_employee_id],
        );
        $firstUpdatedAt = $setting->updated_at;
        $this->travel(1)->second();
        $this->put(route('admin.settings.update', SystemSettingCatalog::NO_SHOW_TIMEOUT), [
            'value' => '60',
        ])->assertRedirect();
        $this->assertSame('60', $setting->fresh()->value);
        $this->assertTrue($setting->fresh()->updated_at->greaterThan($firstUpdatedAt));
    }

    public function test_non_admin_contexts_missing_permission_and_disabled_actors_are_blocked(): void
    {
        foreach (['manager', 'staff', 'kitchen', 'customer'] as $role) {
            $this->actingAs($this->user($role, $role !== 'customer'))
                ->get(route('admin.settings.index'))
                ->assertForbidden();
        }
        auth()->logout();
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));

        $admin = $this->user('admin');
        $admin->role->permissions()->detach(Permission::where('code', 'settings.update')->firstOrFail());
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertForbidden();
        $disabledUser = $this->user('admin');
        $disabledUser->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->actingAs($disabledUser)->get(route('admin.settings.index'))->assertRedirect(route('login'));
        $disabledEmployee = $this->user('admin');
        $disabledEmployee->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->actingAs($disabledEmployee)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_arbitrary_key_and_server_owned_fields_are_rejected(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)
            ->put('/admin/settings/app_key', ['value' => 'secret'])
            ->assertNotFound();
        foreach (['key', 'type', 'updated_by_employee_id', 'created_at', 'updated_at'] as $field) {
            $this->put(route('admin.settings.update', SystemSettingCatalog::NO_SHOW_TIMEOUT), [
                'value' => '30',
                $field => 'forged',
            ])->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('system_settings', 0);
    }

    public function test_timeout_accepts_only_canonical_integer_in_conservative_range(): void
    {
        $service = app(UpdateSystemSettingService::class);
        $admin = $this->user('admin');
        foreach (['0', '-1', '1.5', 'text', '01', '1441', '999999999999999999999'] as $value) {
            try {
                $service->update(SystemSettingCatalog::NO_SHOW_TIMEOUT, $value, $admin);
                $this->fail("Value {$value} must be rejected.");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $service->update(SystemSettingCatalog::NO_SHOW_TIMEOUT, '1440', $admin);
        $this->assertDatabaseHas('system_settings', ['value' => '1440', 'type' => 'integer']);
    }

    public function test_boolean_service_boundary_accepts_only_exact_canonical_strings(): void
    {
        $service = app(UpdateSystemSettingService::class);
        $admin = $this->user('admin');
        foreach (['1', '0', 'yes', 'on', 'TRUE', true, false] as $value) {
            try {
                $service->update(SystemSettingCatalog::CUSTOMER_ORDERING, $value, $admin);
                $this->fail('Truthy coercion must be rejected.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        foreach (['true', 'false'] as $value) {
            $service->update(SystemSettingCatalog::CUSTOMER_ORDERING, $value, $admin);
            $this->assertDatabaseHas('system_settings', [
                'key' => SystemSettingCatalog::CUSTOMER_ORDERING,
                'value' => $value,
                'type' => 'boolean',
            ]);
        }
    }

    public function test_delivery_fee_accepts_canonical_vnd_amount_and_fails_closed(): void
    {
        $service = app(UpdateSystemSettingService::class);
        $resolver = app(TypedSystemSettingResolver::class);
        $admin = $this->user('admin');

        $this->assertNull($resolver->deliveryFee());
        foreach (['-1', '1.5', '030000', '10000001', 'text'] as $value) {
            try {
                $service->update(SystemSettingCatalog::DELIVERY_FEE, $value, $admin);
                $this->fail("Value {$value} must be rejected.");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $service->update(SystemSettingCatalog::DELIVERY_FEE, '30000', $admin);
        $this->assertSame(30000, $resolver->deliveryFee());
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSettingCatalog::DELIVERY_FEE,
            'value' => '30000',
            'type' => 'integer',
        ]);
    }

    public function test_missing_and_malformed_values_fail_closed_and_invalid_row_can_be_repaired(): void
    {
        $resolver = app(TypedSystemSettingResolver::class);
        $this->assertNull($resolver->noShowTimeoutMinutes());
        $this->assertFalse(app(CustomerOrderingCapability::class)->enabled());
        $invalid = SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::NO_SHOW_TIMEOUT,
            'value' => '0',
            'type' => 'integer',
        ]);
        $this->assertNull($resolver->noShowTimeoutMinutes());
        $admin = $this->user('admin');
        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['tab' => 'operation']))
            ->assertOk()
            ->assertSee('name="values[no_show_timeout_minutes]"', false)
            ->assertDontSee('Giá trị hiện tại');
        $this->put(route('admin.settings.update', $invalid->key), ['value' => '15'])->assertRedirect();
        $this->assertSame(15, $resolver->noShowTimeoutMinutes());
    }

    public function test_persistence_failure_rolls_back_value_type_and_actor(): void
    {
        $admin = $this->user('admin');
        $setting = SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::NO_SHOW_TIMEOUT,
            'value' => '30',
            'type' => 'integer',
            'updated_by_employee_id' => $admin->employee->id,
        ]);
        Event::listen('eloquent.updating: '.SystemSetting::class, fn () => throw new RuntimeException('failed'));
        try {
            app(UpdateSystemSettingService::class)->update($setting->key, '60', $admin);
            $this->fail('Expected persistence failure.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
        $this->assertDatabaseHas('system_settings', [
            'id' => $setting->id,
            'value' => '30',
            'type' => 'integer',
            'updated_by_employee_id' => $admin->employee->id,
        ]);
    }

    public function test_settings_get_is_read_only(): void
    {
        $admin = $this->user('admin');
        $setting = SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::CUSTOMER_ORDERING,
            'value' => 'true',
            'type' => 'boolean',
            'updated_by_employee_id' => $admin->employee->id,
        ]);
        $before = $setting->updated_at;
        $this->travel(1)->minute();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();
        $this->assertSame($before->toISOString(), $setting->fresh()->updated_at->toISOString());
        $this->assertDatabaseCount('system_settings', 1);
    }

    public function test_every_catalog_setting_has_a_working_update_route(): void
    {
        $admin = $this->user('admin');
        $values = [
            SystemSettingCatalog::NO_SHOW_TIMEOUT => '30',
            SystemSettingCatalog::CUSTOMER_ORDERING => 'true',
            SystemSettingCatalog::DELIVERY_FEE => '30000',
            SystemSettingCatalog::VIETQR_BANK_ID => 'VCB',
            SystemSettingCatalog::VIETQR_ACCOUNT_NUMBER => '123456789',
            SystemSettingCatalog::VIETQR_ACCOUNT_NAME => 'BEER GARDEN',
            SystemSettingCatalog::VIETQR_TRANSFER_PREFIX => 'BG',
            SystemSettingCatalog::GOOGLE_OAUTH_CLIENT_ID => '123456.apps.googleusercontent.com',
            SystemSettingCatalog::GOOGLE_OAUTH_CLIENT_SECRET => 'test-client-secret',
            SystemSettingCatalog::GOOGLE_TRANSLATION_PROJECT_ID => 'beer-garden',
            SystemSettingCatalog::GOOGLE_TRANSLATION_CREDENTIALS => json_encode(
                [
                    'type' => 'service_account',
                    'client_email' => 'translate@example.test',
                    'private_key' => "-----BEGIN PRIVATE KEY-----\ntest\n-----END PRIVATE KEY-----\n",
                ],
                JSON_THROW_ON_ERROR,
            ),
        ];

        $this->actingAs($admin);
        foreach ($values as $key => $value) {
            $this->put(route('admin.settings.update', $key), ['value' => $value])->assertRedirect(
                route('admin.settings.index'),
            );
        }

        $this->assertDatabaseCount('system_settings', count($values));
        $this->assertDatabaseMissing('system_settings', [
            'key' => SystemSettingCatalog::GOOGLE_OAUTH_CLIENT_SECRET,
            'value' => 'test-client-secret',
        ]);
    }

    public function test_tabbed_settings_save_a_group_and_replace_uploaded_images(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin');
        $seoPage = $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('SEO &amp; Thông tin', false)
            ->assertSee('Lưu cài đặt')
            ->assertSee('Làm mới')
            ->assertDontSee('Giá trị hiện tại')
            ->assertDontSee('Cập nhật gần nhất');
        $this->assertSame(1, substr_count($seoPage->getContent(), 'Lưu cài đặt'));

        $this->get(route('admin.settings.index', ['tab' => 'social']))
            ->assertOk()
            ->assertSee('Open Graph title')
            ->assertDontSee('Tên website');
        $this->get(route('admin.settings.index', ['tab' => 'images']))
            ->assertOk()
            ->assertSee('Logo website')
            ->assertDontSee('Open Graph title');
        $scriptPage = $this->get(route('admin.settings.index', ['tab' => 'scripts']))
            ->assertOk()
            ->assertSee('Mã trong thẻ &lt;head&gt;', false)
            ->assertSee('Mã ngay sau thẻ &lt;body&gt;', false)
            ->assertSee('Mã trước thẻ &lt;/body&gt;', false);
        $this->assertSame(1, substr_count($scriptPage->getContent(), 'Lưu cài đặt'));

        $this->put(route('admin.settings.group.update', 'seo'), [
            'values' => [
                SystemSettingCatalog::SITE_NAME => '89 Beer Garden',
                SystemSettingCatalog::SEO_TITLE => '89 Beer Garden — Vườn bia Việt',
                SystemSettingCatalog::SEO_DESCRIPTION => 'Vườn bia Việt với món ngon và không gian gần gũi.',
            ],
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'seo']));

        $this->put(route('admin.settings.group.update', 'scripts'), [
            'values' => [
                SystemSettingCatalog::CUSTOM_SCRIPT_HEAD => '<meta name="verify" content="89">',
                SystemSettingCatalog::CUSTOM_SCRIPT_BODY => '<noscript>tracking</noscript>',
                SystemSettingCatalog::CUSTOM_SCRIPT_FOOTER => '<script>window.ready=true;</script>',
            ],
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'scripts']));
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSettingCatalog::CUSTOM_SCRIPT_FOOTER,
            'value' => '<script>window.ready=true;</script>',
        ]);

        $this->put(route('admin.settings.group.update', 'images'), [
            'images' => [SystemSettingCatalog::SITE_LOGO => UploadedFile::fake()->image('logo.png', 600, 300)],
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'images']));
        $firstPath = SystemSetting::query()->where('key', SystemSettingCatalog::SITE_LOGO)->value('value');
        Storage::disk('public')->assertExists($firstPath);

        $this->put(route('admin.settings.group.update', 'images'), [
            'images' => [SystemSettingCatalog::SITE_LOGO => UploadedFile::fake()->image('logo-new.webp', 600, 300)],
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'images']));
        $newPath = SystemSetting::query()->where('key', SystemSettingCatalog::SITE_LOGO)->value('value');

        $this->assertNotSame($firstPath, $newPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($newPath);
        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSettingCatalog::SEO_TITLE,
            'value' => '89 Beer Garden — Vườn bia Việt',
        ]);
    }

    public function test_public_layout_uses_configured_seo_social_and_brand_values(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin');
        $service = app(UpdateSystemSettingService::class);
        $values = [
            SystemSettingCatalog::SITE_NAME => '89 Beer Garden',
            SystemSettingCatalog::SEO_TITLE => 'Vườn bia Việt tại Hà Nội',
            SystemSettingCatalog::SEO_DESCRIPTION => 'Không gian vườn bia Việt, món ngon và những câu chuyện vui.',
            SystemSettingCatalog::OG_TITLE => 'Hẹn nhau ở 89 Beer Garden',
            SystemSettingCatalog::TWITTER_CARD => 'summary_large_image',
            SystemSettingCatalog::CUSTOM_SCRIPT_HEAD => '<meta name="site-verification" content="beer-89">',
            SystemSettingCatalog::CUSTOM_SCRIPT_BODY => '<noscript id="tracking-fallback">tracking</noscript>',
            SystemSettingCatalog::CUSTOM_SCRIPT_FOOTER => '<script>window.beerGardenReady=true;</script>',
        ];
        foreach ($values as $key => $value) {
            $service->update($key, $value, $admin);
        }

        $this->get(route('customer.home'))
            ->assertOk()
            ->assertSee('<title>Vườn bia Việt tại Hà Nội</title>', false)
            ->assertSee('name="description" content="Không gian vườn bia Việt, món ngon và những câu chuyện vui."', false)
            ->assertSee('property="og:title" content="Hẹn nhau ở 89 Beer Garden"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('<meta name="site-verification" content="beer-89">', false)
            ->assertSee('<noscript id="tracking-fallback">tracking</noscript>', false)
            ->assertSee('<script>window.beerGardenReady=true;</script>', false)
            ->assertSee('89 Beer Garden');
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        if ($employee) {
            Employee::query()->forceCreate([
                'user_id' => $user->id,
                'employee_code' => 'SET-'.fake()->unique()->numberBetween(1, 999999),
                'name' => ucfirst($role),
                'status' => EmployeeStatus::Active,
            ]);
        }

        return $user;
    }
}
