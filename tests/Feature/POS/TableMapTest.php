<?php

namespace Tests\Feature\POS;

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

class TableMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_table_map_and_transition_have_independent_permissions(): void
    {
        $table = $this->table('Cleaning table', 4, RestaurantTableStatus::Cleaning);
        $staff = $this->user('staff');

        $this->get(route('pos.tables.index'))->assertRedirect(route('login'));
        $this->actingAs($staff)->get(route('pos.tables.index'))->assertOk()->assertSee('Cleaning table');

        $staff->role->permissions()->detach(Permission::where('code', 'table.operate')->firstOrFail());
        $this->patch(route('pos.tables.mark-available', $table))->assertForbidden();
        $staff->role->permissions()->attach(Permission::where('code', 'table.operate')->firstOrFail());
        $staff->role->permissions()->detach(Permission::where('code', 'table.view')->firstOrFail());
        $this->get(route('pos.tables.index'))->assertForbidden();

        $this->actingAs($this->user('customer', false))->get(route('pos.tables.index'))->assertForbidden();
    }

    public function test_map_searches_and_filters_status_location_and_configuration_state(): void
    {
        $this->table('Patio Available', 4, RestaurantTableStatus::Available, true, 'Patio');
        $this->table('Hall Cleaning', 6, RestaurantTableStatus::Cleaning, true, 'Hall');
        $this->table('Patio Inactive', 8, RestaurantTableStatus::Available, false, 'Patio');

        $this->actingAs($this->user('staff'))
            ->get(
                route('pos.tables.index', [
                    'q' => 'Patio',
                    'status' => 'available',
                    'location' => 'Patio',
                    'active' => '1',
                ]),
            )
            ->assertOk()
            ->assertSee('Patio Available')
            ->assertDontSee('Hall Cleaning')
            ->assertDontSee('Patio Inactive');
    }

    public function test_party_size_suggestion_only_returns_active_available_non_deleted_tables_without_active_session(): void
    {
        $suitable = $this->table('Suitable', 6);
        $this->table('Too small', 2);
        $this->table('Reserved', 10, RestaurantTableStatus::Reserved);
        $this->table('Inactive', 10, RestaurantTableStatus::Available, false);
        $deleted = $this->table('Deleted', 10);
        $deleted->delete();
        $drifted = $this->table('Has active session', 10);
        $this->diningSession($drifted);

        $this->actingAs($this->user('staff'))
            ->get(route('pos.tables.index', ['party_size' => 5]))
            ->assertOk()
            ->assertSee($suitable->name)
            ->assertDontSee('Too small')
            ->assertDontSee('Reserved')
            ->assertDontSee('Inactive')
            ->assertDontSee('Deleted')
            ->assertDontSee('Has active session');
    }

    public function test_only_cleaning_to_available_transition_is_accepted_and_double_submit_is_safe(): void
    {
        $staff = $this->user('staff');
        $cleaning = $this->table('Cleaning', 4, RestaurantTableStatus::Cleaning);

        $this->actingAs($staff)
            ->patch(route('pos.tables.mark-available', $cleaning), ['runtime_status' => 'occupied'])
            ->assertSessionHasErrors('runtime_status');
        $this->assertSame(RestaurantTableStatus::Cleaning, $cleaning->fresh()->runtime_status);

        $this->patch(route('pos.tables.mark-available', $cleaning))->assertRedirect();
        $this->assertSame(RestaurantTableStatus::Available, $cleaning->fresh()->runtime_status);
        $this->patch(route('pos.tables.mark-available', $cleaning))->assertSessionHasErrors('table');
        $this->assertSame(RestaurantTableStatus::Available, $cleaning->fresh()->runtime_status);

        $occupied = $this->table('Occupied', 4, RestaurantTableStatus::Occupied);
        $this->patch(route('pos.tables.mark-available', $occupied))->assertSessionHasErrors('table');
        $this->assertSame(RestaurantTableStatus::Occupied, $occupied->fresh()->runtime_status);

        $reserved = $this->table('Reserved', 4, RestaurantTableStatus::Reserved);
        $this->patch(route('pos.tables.mark-available', $reserved))->assertSessionHasErrors('table');
        $this->assertSame(RestaurantTableStatus::Reserved, $reserved->fresh()->runtime_status);
    }

    public function test_active_session_blocks_cleaning_transition_and_relationship_is_unchanged(): void
    {
        $table = $this->table('Drifted cleaning', 4, RestaurantTableStatus::Cleaning);
        $session = $this->diningSession($table);

        $this->actingAs($this->user('staff'))
            ->patch(route('pos.tables.mark-available', $table))
            ->assertSessionHasErrors('table');
        $this->assertSame(RestaurantTableStatus::Cleaning, $table->fresh()->runtime_status);
        $this->assertDatabaseHas('dining_sessions', [
            'id' => $session->id,
            'table_id' => $table->id,
            'status' => 'active',
        ]);
    }

    private function table(
        string $name,
        int $capacity,
        RestaurantTableStatus $status = RestaurantTableStatus::Available,
        bool $active = true,
        string $location = 'Main',
    ): RestaurantTable {
        return RestaurantTable::query()->forceCreate([
            'code' => 'T-'.fake()->unique()->numberBetween(1, 999999),
            'name' => $name,
            'capacity' => $capacity,
            'location' => $location,
            'runtime_status' => $status,
            'is_active' => $active,
        ]);
    }

    private function diningSession(RestaurantTable $table): DiningSession
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
            'status' => DiningSessionStatus::Active,
            'started_at' => now(),
            'guest_count' => 2,
        ]);
    }

    private function user(string $role, bool $employee = true): User
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
