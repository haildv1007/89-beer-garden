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
use Illuminate\Support\Facades\Event;
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
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()
            ->assertSee(__('setting.title'))->assertSee('no_show_timeout_minutes')->assertDontSee((string) config('app.key'));
        $this->assertDatabaseCount('system_settings', 0);

        $this->put(route('admin.settings.update', SystemSettingCatalog::NO_SHOW_TIMEOUT), ['value' => '30'])->assertRedirect();
        $setting = SystemSetting::query()->sole();
        $this->assertSame(['30', 'integer', $admin->employee->id], [$setting->value, $setting->type, $setting->updated_by_employee_id]);
        $firstUpdatedAt = $setting->updated_at;
        $this->travel(1)->second();
        $this->put(route('admin.settings.update', SystemSettingCatalog::NO_SHOW_TIMEOUT), ['value' => '60'])->assertRedirect();
        $this->assertSame('60', $setting->fresh()->value);
        $this->assertTrue($setting->fresh()->updated_at->greaterThan($firstUpdatedAt));
    }

    public function test_non_admin_contexts_missing_permission_and_disabled_actors_are_blocked(): void
    {
        foreach (['manager', 'staff', 'kitchen', 'customer'] as $role) {
            $this->actingAs($this->user($role, $role !== 'customer'))->get(route('admin.settings.index'))->assertForbidden();
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
        $this->actingAs($admin)->put('/admin/settings/app_key', ['value' => 'secret'])->assertNotFound();
        foreach (['key', 'type', 'updated_by_employee_id', 'created_at', 'updated_at'] as $field) {
            $this->put(route('admin.settings.update', SystemSettingCatalog::NO_SHOW_TIMEOUT),
                ['value' => '30', $field => 'forged'])->assertSessionHasErrors($field);
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
            $this->assertDatabaseHas('system_settings', ['key' => SystemSettingCatalog::CUSTOMER_ORDERING,
                'value' => $value, 'type' => 'boolean']);
        }
    }

    public function test_missing_and_malformed_values_fail_closed_and_invalid_row_can_be_repaired(): void
    {
        $resolver = app(TypedSystemSettingResolver::class);
        $this->assertNull($resolver->noShowTimeoutMinutes());
        $this->assertFalse(app(CustomerOrderingCapability::class)->enabled());
        $invalid = SystemSetting::query()->forceCreate(['key' => SystemSettingCatalog::NO_SHOW_TIMEOUT,
            'value' => '0', 'type' => 'integer']);
        $this->assertNull($resolver->noShowTimeoutMinutes());
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee(__('setting.invalid'));
        $this->put(route('admin.settings.update', $invalid->key), ['value' => '15'])->assertRedirect();
        $this->assertSame(15, $resolver->noShowTimeoutMinutes());
    }

    public function test_persistence_failure_rolls_back_value_type_and_actor(): void
    {
        $admin = $this->user('admin');
        $setting = SystemSetting::query()->forceCreate(['key' => SystemSettingCatalog::NO_SHOW_TIMEOUT,
            'value' => '30', 'type' => 'integer', 'updated_by_employee_id' => $admin->employee->id]);
        Event::listen('eloquent.updating: '.SystemSetting::class, fn () => throw new RuntimeException('failed'));
        try {
            app(UpdateSystemSettingService::class)->update($setting->key, '60', $admin);
            $this->fail('Expected persistence failure.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
        $this->assertDatabaseHas('system_settings', ['id' => $setting->id, 'value' => '30',
            'type' => 'integer', 'updated_by_employee_id' => $admin->employee->id]);
    }

    public function test_settings_get_is_read_only(): void
    {
        $admin = $this->user('admin');
        $setting = SystemSetting::query()->forceCreate(['key' => SystemSettingCatalog::CUSTOMER_ORDERING,
            'value' => 'true', 'type' => 'boolean', 'updated_by_employee_id' => $admin->employee->id]);
        $before = $setting->updated_at;
        $this->travel(1)->minute();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();
        $this->assertSame($before->toISOString(), $setting->fresh()->updated_at->toISOString());
        $this->assertDatabaseCount('system_settings', 1);
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        if ($employee) {
            Employee::query()->forceCreate(['user_id' => $user->id,
                'employee_code' => 'SET-'.fake()->unique()->numberBetween(1, 999999),
                'name' => ucfirst($role), 'status' => EmployeeStatus::Active]);
        }

        return $user;
    }
}
