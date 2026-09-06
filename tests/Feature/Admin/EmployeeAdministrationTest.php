<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_employee_routes_enforce_context_employee_and_independent_permissions(): void
    {
        $this->get(route('admin.employees.index'))->assertRedirect(route('login'));

        foreach (['customer', 'staff', 'kitchen'] as $role) {
            $this->actingAs($this->user($role, $role !== 'customer'))
                ->get(route('admin.employees.index'))
                ->assertForbidden();
        }

        $manager = $this->user('manager', true);
        $admin = $this->user('admin', true);
        $this->actingAs($manager)->get(route('admin.employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.employees.index'))->assertOk();

        $manager->role->permissions()->detach(Permission::where('code', 'employee.manage')->firstOrFail());
        $this->actingAs($manager)->get(route('admin.employees.index'))->assertForbidden();

        $this->actingAs($this->user('admin'))->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($this->user('admin', true, EmployeeStatus::Disabled))
            ->get(route('admin.employees.index'))
            ->assertForbidden();
    }

    public function test_employee_profile_crud_search_filter_and_security_fields(): void
    {
        $manager = $this->user('manager', true);
        $payload = [
            'employee_code' => 'E-100',
            'name' => 'Lan Nguyen',
            'phone' => '0901000000',
            'position' => 'Server',
        ];
        $this->actingAs($manager)->post(route('admin.employees.store'), $payload)->assertRedirect();
        $employee = Employee::where('employee_code', 'E-100')->firstOrFail();
        $this->assertNull($employee->user_id);
        $this->assertSame(EmployeeStatus::Active, $employee->status);

        $this->get(route('admin.employees.index', ['q' => '0901', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Lan Nguyen');
        $this->post(route('admin.employees.store'), $payload)->assertSessionHasErrors('employee_code');

        $this->put(
            route('admin.employees.update', $employee),
            $payload + [
                'name' => 'Forged',
                'status' => 'disabled',
                'role_id' => Role::where('code', 'admin')->value('id'),
                'user_id' => $manager->id,
                'password' => 'Leaked#Password123',
            ],
        )->assertSessionHasErrors(['status', 'role_id', 'user_id', 'password']);
        $this->assertSame('Lan Nguyen', $employee->fresh()->name);

        $this->put(
            route('admin.employees.update', $employee),
            array_merge($payload, ['name' => 'Lan Updated']),
        )->assertRedirect();
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Lan Updated', 'status' => 'active']);
    }

    public function test_only_permission_assign_can_create_account_and_change_to_employee_role(): void
    {
        $employee = $this->employee();
        $manager = $this->user('manager', true);
        $admin = $this->user('admin', true);
        $staffRole = Role::where('code', 'staff')->firstOrFail();
        $kitchenRole = Role::where('code', 'kitchen')->firstOrFail();
        $customerRole = Role::where('code', 'customer')->firstOrFail();
        $payload = [
            'email' => 'WORKER@EXAMPLE.COM',
            'password' => 'Secure#Pass123',
            'password_confirmation' => 'Secure#Pass123',
            'role_id' => $staffRole->id,
        ];

        $this->actingAs($manager)
            ->post(route('admin.employees.accounts.store', $employee), $payload)
            ->assertForbidden();
        $this->assertNull($employee->fresh()->user_id);

        $this->actingAs($admin)->post(route('admin.employees.accounts.store', $employee), $payload)->assertRedirect();
        $user = $employee->fresh()->user;
        $this->assertSame('worker@example.com', $user->email);
        $this->assertTrue(Hash::check('Secure#Pass123', $user->password));
        $this->assertSame($staffRole->id, $user->role_id);

        $this->post(
            route('admin.employees.accounts.store', $employee),
            array_merge($payload, ['email' => 'second@example.com']),
        )->assertSessionHasErrors('employee');
        $this->actingAs($manager)
            ->patch(route('admin.employees.roles.update', $employee), ['role_id' => $kitchenRole->id])
            ->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('admin.employees.roles.update', $employee), ['role_id' => $customerRole->id])
            ->assertSessionHasErrors('role_id');
        $this->patch(route('admin.employees.roles.update', $employee), [
            'role_id' => $kitchenRole->id,
        ])->assertRedirect();
        $this->assertSame($kitchenRole->id, $user->fresh()->role_id);
    }

    public function test_account_validation_enforces_unique_email_password_and_never_flashes_passwords(): void
    {
        $admin = $this->user('admin', true);
        $existing = $this->user('staff', true);
        $employee = $this->employee();
        $payload = [
            'email' => $existing->email,
            'password' => 'short',
            'password_confirmation' => 'different',
            'role_id' => 999999,
        ];

        $this->actingAs($admin)
            ->from(route('admin.employees.show', $employee))
            ->post(route('admin.employees.accounts.store', $employee), $payload)
            ->assertRedirect(route('admin.employees.show', $employee))
            ->assertSessionHasErrors(['email', 'password', 'role_id']);
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
        $this->assertNull($employee->fresh()->user_id);
    }

    public function test_disable_is_permission_guarded_atomic_and_preserves_identity(): void
    {
        $targetUser = $this->user('staff', true);
        $target = $targetUser->employee;
        $manager = $this->user('manager', true);
        $manager->role->permissions()->detach(Permission::where('code', 'employee.disable')->firstOrFail());

        $this->actingAs($manager)->patch(route('admin.employees.disable', $target))->assertForbidden();
        $this->assertSame(EmployeeStatus::Active, $target->fresh()->status);

        $admin = $this->user('admin', true);
        $this->actingAs($admin)->patch(route('admin.employees.disable', $target))->assertRedirect();
        $this->assertSame(EmployeeStatus::Disabled, $target->fresh()->status);
        $this->assertSame(User::STATUS_DISABLED, $targetUser->fresh()->status);
        $this->assertDatabaseHas('employees', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
        $this->actingAs($targetUser->fresh())->get(route('pos.home'))->assertRedirect(route('login'));
    }

    public function test_role_permission_matrix_validates_catalog_and_revocation_applies_next_request(): void
    {
        $manager = $this->user('manager', true);
        $this->actingAs($manager)->get(route('admin.roles.index'))->assertForbidden();

        $admin = $this->user('admin', true);
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
        $managerRole = $manager->role;
        $original = $managerRole->permissions()->pluck('permissions.id')->all();
        $this->actingAs($admin)
            ->put(route('admin.roles.permissions.update', $managerRole), [
                'permissions' => array_merge($original, [999999]),
            ])
            ->assertSessionHasErrors('permissions.'.count($original));
        $this->assertEqualsCanonicalizing($original, $managerRole->permissions()->pluck('permissions.id')->all());

        $employeeManageId = Permission::where('code', 'employee.manage')->value('id');
        $remaining = array_values(array_diff($original, [$employeeManageId]));
        $this->put(route('admin.roles.permissions.update', $managerRole), [
            'permissions' => $remaining,
        ])->assertRedirect();
        $this->actingAs($manager)->get(route('admin.employees.index'))->assertForbidden();
        $this->assertSame(
            Role::CANONICAL_CODES,
            Role::query()
                ->orderByRaw("FIELD(code, 'customer','staff','kitchen','manager','admin')")
                ->pluck('code')
                ->all(),
        );
        $this->assertEqualsCanonicalizing(array_keys(Permission::CATALOG), Permission::query()->pluck('code')->all());
    }

    public function test_admin_role_cannot_lose_mandatory_permissions_but_non_mandatory_grants_can_change(): void
    {
        $admin = $this->user('admin', true);
        $adminRole = $admin->role;
        $idsByCode = Permission::query()->pluck('id', 'code');
        $original = $adminRole->permissions()->pluck('permissions.id')->all();

        foreach (['context.admin.access', 'permission.assign'] as $mandatory) {
            $withoutMandatory = array_values(array_diff($original, [$idsByCode[$mandatory]]));
            $this->actingAs($admin)
                ->put(route('admin.roles.permissions.update', $adminRole), ['permissions' => $withoutMandatory])
                ->assertSessionHasErrors('permissions');
            $this->assertEqualsCanonicalizing($original, $adminRole->permissions()->pluck('permissions.id')->all());
        }

        $withoutProductManagement = array_values(array_diff($original, [$idsByCode['product.manage']]));
        $this->put(route('admin.roles.permissions.update', $adminRole), [
            'permissions' => $withoutProductManagement,
        ])->assertRedirect();
        $this->assertTrue($adminRole->permissions()->where('code', 'context.admin.access')->exists());
        $this->assertTrue($adminRole->permissions()->where('code', 'permission.assign')->exists());
        $this->assertFalse($adminRole->permissions()->where('code', 'product.manage')->exists());
    }

    public function test_manager_cannot_disable_admin_even_with_employee_disable_permission(): void
    {
        $manager = $this->user('manager', true);
        $targetAdmin = $this->user('admin', true);

        $this->actingAs($manager)->patch(route('admin.employees.disable', $targetAdmin->employee))->assertForbidden();
        $this->assertSame(User::STATUS_ACTIVE, $targetAdmin->fresh()->status);
        $this->assertSame(EmployeeStatus::Active, $targetAdmin->employee->fresh()->status);
    }

    public function test_admin_can_disable_another_admin_when_a_valid_admin_remains(): void
    {
        $actor = $this->user('admin', true);
        $target = $this->user('admin', true);
        $remaining = $this->user('admin', true);

        $this->actingAs($actor)->patch(route('admin.employees.disable', $target->employee))->assertRedirect();
        $this->assertSame(User::STATUS_DISABLED, $target->fresh()->status);
        $this->assertSame(EmployeeStatus::Disabled, $target->employee->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $remaining->fresh()->status);
    }

    public function test_last_active_admin_cannot_disable_self(): void
    {
        $admin = $this->user('admin', true);

        $this->actingAs($admin)
            ->patch(route('admin.employees.disable', $admin->employee))
            ->assertSessionHasErrors('employee');
        $this->assertSame(User::STATUS_ACTIVE, $admin->fresh()->status);
        $this->assertSame(EmployeeStatus::Active, $admin->employee->fresh()->status);
    }

    public function test_admin_can_self_disable_when_another_valid_admin_remains(): void
    {
        $admin = $this->user('admin', true);
        $remaining = $this->user('admin', true);

        $this->actingAs($admin)->patch(route('admin.employees.disable', $admin->employee))->assertRedirect();
        $this->assertSame(User::STATUS_DISABLED, $admin->fresh()->status);
        $this->assertSame(EmployeeStatus::Disabled, $admin->employee->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $remaining->fresh()->status);
        $this->assertSame(EmployeeStatus::Active, $remaining->employee->fresh()->status);
    }

    public function test_permission_matrix_can_sync_an_intentionally_empty_grant_set(): void
    {
        $admin = $this->user('admin', true);
        $staffRole = Role::where('code', 'staff')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.roles.permissions.update', $staffRole), [])->assertRedirect();
        $this->assertSame(0, $staffRole->permissions()->count());
    }

    public function test_ui_hides_sensitive_actions_while_forged_requests_stay_denied(): void
    {
        $employee = $this->employee();
        $manager = $this->user('manager', true);

        $this->actingAs($manager)
            ->get(route('admin.employees.show', $employee))
            ->assertOk()
            ->assertDontSee(__('employee.accounts.create'));
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->post(route('admin.employees.accounts.store', $employee), [
            'email' => 'forged@example.com',
            'password' => 'Secure#Pass123',
            'password_confirmation' => 'Secure#Pass123',
            'role_id' => Role::where('code', 'admin')->value('id'),
        ])->assertForbidden();
    }

    private function employee(): Employee
    {
        return Employee::query()->forceCreate([
            'employee_code' => 'E-'.fake()->unique()->numberBetween(1000, 999999),
            'name' => 'Employee',
            'status' => EmployeeStatus::Active,
        ]);
    }

    private function user(string $role, bool $employee = false, EmployeeStatus $status = EmployeeStatus::Active): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        if ($employee) {
            Employee::query()->forceCreate([
                'user_id' => $user->id,
                'employee_code' => 'E-'.$user->id.'-'.fake()->unique()->numberBetween(1, 99999),
                'name' => 'Employee '.$user->id,
                'status' => $status,
            ]);
        }

        return $user;
    }
}
