<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_is_migrated_to_administrator_and_roles_link_lives_in_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $legacy = Role::query()->forceCreate(['code' => 'manager', 'name' => 'Manager']);
        $legacyUser = User::factory()->forRole($legacy)->create();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('roles', ['code' => 'manager']);
        $migratedUser = $legacyUser->fresh();
        $this->assertSame('admin', $migratedUser->role->code);
        $this->assertSame('Quản trị viên', $migratedUser->role->name);
        $this->assertSame(['customer', 'staff', 'kitchen', 'admin'], Role::CANONICAL_CODES);
        $this->assertSame(['staff', 'kitchen', 'admin'], Role::EMPLOYEE_CODES);

        $admin = User::factory()->forRole(Role::query()->where('code', 'admin')->firstOrFail())->create();
        Employee::query()->forceCreate([
            'user_id' => $admin->id,
            'employee_code' => 'ROLE-ADMIN',
            'name' => 'Quản trị viên',
            'status' => EmployeeStatus::Active,
        ]);

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee(route('admin.roles.index'))
            ->assertSee('Vai trò và quyền');
        $this->get(route('admin.roles.index'))->assertOk()->assertDontSee('Manager');
    }
}
