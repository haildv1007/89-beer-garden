<?php

namespace Tests\Feature\Admin;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTableManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_table_routes_require_context_employee_and_manage_permission(): void
    {
        $this->get(route('admin.restaurant-tables.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user('staff', true))->get(route('admin.restaurant-tables.index'))->assertForbidden();

        $manager = $this->user('manager', true);
        $admin = $this->user('admin', true);
        $this->actingAs($manager)->get(route('admin.restaurant-tables.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.restaurant-tables.index'))->assertOk();
        $manager->role->permissions()->detach(Permission::where('code', 'restaurant-table.manage')->firstOrFail());
        $this->actingAs($manager)->get(route('admin.restaurant-tables.index'))->assertForbidden();

        $this->actingAs($this->user('admin'))->get(route('admin.restaurant-tables.index'))->assertForbidden();
    }

    public function test_crud_validates_configuration_and_keeps_runtime_status_server_owned(): void
    {
        $manager = $this->user('manager', true);
        $payload = ['code' => 'A-01', 'name' => 'Patio 1', 'capacity' => 4, 'location' => 'Patio', 'is_active' => true];

        $this->actingAs($manager)
            ->post(route('admin.restaurant-tables.store'), $payload + ['runtime_status' => 'occupied'])
            ->assertSessionHasErrors('runtime_status');
        $this->assertDatabaseMissing('restaurant_tables', ['code' => 'A-01']);

        $this->post(route('admin.restaurant-tables.store'), $payload)->assertRedirect();
        $table = RestaurantTable::where('code', 'A-01')->firstOrFail();
        $this->assertSame(RestaurantTableStatus::Available, $table->runtime_status);
        $this->assertTrue($table->is_active);

        $this->post(route('admin.restaurant-tables.store'), $payload)->assertSessionHasErrors('code');
        $this->put(
            route('admin.restaurant-tables.update', $table),
            array_merge($payload, ['capacity' => 0]),
        )->assertSessionHasErrors('capacity');
        $this->put(
            route('admin.restaurant-tables.update', $table),
            array_merge($payload, ['name' => 'Forged', 'runtime_status' => 'cleaning']),
        )->assertSessionHasErrors('runtime_status');
        $this->assertSame(RestaurantTableStatus::Available, $table->fresh()->runtime_status);

        $this->put(
            route('admin.restaurant-tables.update', $table),
            array_merge($payload, ['name' => 'Patio Updated', 'capacity' => 6]),
        )->assertRedirect();
        $this->assertDatabaseHas('restaurant_tables', [
            'id' => $table->id,
            'name' => 'Patio Updated',
            'capacity' => 6,
            'runtime_status' => 'available',
        ]);
    }

    public function test_list_searches_and_filters_runtime_configuration_and_minimum_capacity(): void
    {
        $this->table()
            ->forceFill(['name' => 'Main Large', 'capacity' => 8, 'location' => 'Hall'])
            ->save();
        $this->table(RestaurantTableStatus::Cleaning)
            ->forceFill(['name' => 'Patio Small', 'capacity' => 2, 'location' => 'Patio'])
            ->save();
        $inactive = $this->table();
        $inactive->forceFill(['name' => 'Main Inactive', 'capacity' => 10, 'is_active' => false])->save();

        $this->actingAs($this->user('manager', true))
            ->get(
                route('admin.restaurant-tables.index', [
                    'q' => 'Main',
                    'status' => 'available',
                    'active' => '1',
                    'min_capacity' => 6,
                ]),
            )
            ->assertOk()
            ->assertSee('Main Large')
            ->assertDontSee('Patio Small')
            ->assertDontSee('Main Inactive');
    }

    public function test_list_exposes_direct_actions_and_active_session_without_a_detail_step(): void
    {
        $table = $this->table();
        $employee = Employee::query()->forceCreate([
            'employee_code' => 'E-LIST',
            'name' => 'Server',
            'status' => EmployeeStatus::Active,
        ]);
        $session = DiningSession::query()->forceCreate([
            'session_code' => 'DS-01M0TCT194MZYNK6P4TM7W9CHG',
            'table_id' => $table->id,
            'opened_by_employee_id' => $employee->id,
            'status' => DiningSessionStatus::Active,
            'started_at' => now(),
            'guest_count' => 4,
        ]);

        $this->actingAs($this->user('manager', true))
            ->get(route('admin.restaurant-tables.index'))
            ->assertOk()
            ->assertSee(route('admin.restaurant-tables.edit', $table))
            ->assertSee(route('admin.restaurant-tables.destroy', $table))
            ->assertSee(route('admin.dining-sessions.show', $session))
            ->assertSee('DS-TM7W9CHG')
            ->assertDontSee('Xem chi tiết');
    }

    public function test_available_table_without_active_session_can_be_deactivated_and_soft_deleted(): void
    {
        $manager = $this->user('manager', true);
        $table = $this->table();
        $payload = [
            'code' => $table->code,
            'name' => $table->name,
            'capacity' => $table->capacity,
            'location' => $table->location,
            'is_active' => false,
        ];

        $this->actingAs($manager)->put(route('admin.restaurant-tables.update', $table), $payload)->assertRedirect();
        $this->assertFalse($table->fresh()->is_active);

        $this->delete(route('admin.restaurant-tables.destroy', $table))->assertRedirect();
        $this->assertSoftDeleted('restaurant_tables', ['id' => $table->id]);
    }

    public function test_deactivate_and_delete_fail_closed_for_non_available_runtime_states(): void
    {
        $manager = $this->user('manager', true);

        foreach (
            [RestaurantTableStatus::Reserved, RestaurantTableStatus::Occupied, RestaurantTableStatus::Cleaning] as $status
        ) {
            $table = $this->table($status);
            $payload = [
                'code' => $table->code,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'location' => $table->location,
                'is_active' => false,
            ];

            $this->actingAs($manager)
                ->put(route('admin.restaurant-tables.update', $table), $payload)
                ->assertSessionHasErrors('table');
            $this->assertTrue($table->fresh()->is_active);
            $this->delete(route('admin.restaurant-tables.destroy', $table))->assertSessionHasErrors('table');
            $this->assertNotSoftDeleted('restaurant_tables', ['id' => $table->id]);
        }
    }

    public function test_active_dining_session_blocks_deactivate_and_delete_even_if_status_drifted_available(): void
    {
        $manager = $this->user('manager', true);
        $table = $this->table();
        $this->diningSession($table, DiningSessionStatus::Active);
        $payload = [
            'code' => $table->code,
            'name' => $table->name,
            'capacity' => $table->capacity,
            'location' => $table->location,
            'is_active' => false,
        ];

        $this->actingAs($manager)
            ->put(route('admin.restaurant-tables.update', $table), $payload)
            ->assertSessionHasErrors('table');
        $this->delete(route('admin.restaurant-tables.destroy', $table))->assertSessionHasErrors('table');
        $this->assertTrue($table->fresh()->is_active);
    }

    public function test_soft_delete_preserves_completed_session_relationship(): void
    {
        $manager = $this->user('manager', true);
        $table = $this->table();
        $session = $this->diningSession($table, DiningSessionStatus::Completed);

        $this->actingAs($manager)->delete(route('admin.restaurant-tables.destroy', $table))->assertRedirect();
        $this->assertDatabaseHas('dining_sessions', ['id' => $session->id, 'table_id' => $table->id]);
        $this->assertSoftDeleted('restaurant_tables', ['id' => $table->id]);
    }

    private function table(RestaurantTableStatus $status = RestaurantTableStatus::Available): RestaurantTable
    {
        return RestaurantTable::query()->forceCreate([
            'code' => 'T-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Table',
            'capacity' => 4,
            'location' => 'Main',
            'runtime_status' => $status,
            'is_active' => true,
        ]);
    }

    private function diningSession(RestaurantTable $table, DiningSessionStatus $status): DiningSession
    {
        $employee = Employee::query()->forceCreate([
            'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Server',
            'status' => EmployeeStatus::Active,
        ]);

        return DiningSession::query()->forceCreate([
            'session_code' => 'S-'.fake()->unique()->numberBetween(1, 999999),
            'table_id' => $table->id,
            'opened_by_employee_id' => $employee->id,
            'status' => $status,
            'started_at' => now(),
            'ended_at' => $status === DiningSessionStatus::Completed ? now() : null,
            'guest_count' => 2,
        ]);
    }

    private function user(string $role, bool $employee = false): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        if ($employee) {
            Employee::query()->forceCreate([
                'user_id' => $user->id,
                'employee_code' => 'E-'.$user->id.'-'.fake()->unique()->numberBetween(1, 99999),
                'name' => 'Employee',
                'status' => EmployeeStatus::Active,
            ]);
        }

        return $user;
    }
}
