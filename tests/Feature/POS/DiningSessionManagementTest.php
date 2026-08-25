<?php

namespace Tests\Feature\POS;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class DiningSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_confirmed_reservation_check_in_is_atomic_and_links_canonical_records(): void
    {
        $staff = $this->user('staff');
        $reservation = $this->reservation(ReservationStatus::Confirmed, 5);
        $table = $this->table(6);

        $response = $this->actingAs($staff)->post(route('pos.reservations.check-in', $reservation), ['table_id' => $table->id]);
        $session = DiningSession::query()->sole();
        $response->assertRedirect(route('pos.dining-sessions.show', $session));

        $reservation->refresh();
        $this->assertSame(ReservationStatus::CheckedIn, $reservation->status);
        $this->assertSame($table->id, $reservation->table_id);
        $this->assertNotNull($reservation->checked_in_at);
        $this->assertSame(DiningSessionStatus::Active, $session->status);
        $this->assertSame($reservation->id, $session->reservation_id);
        $this->assertSame($reservation->customer_id, $session->customer_id);
        $this->assertSame($staff->employee->id, $session->opened_by_employee_id);
        $this->assertSame(5, $session->guest_count);
        $this->assertSame(RestaurantTableStatus::Occupied, $table->fresh()->runtime_status);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_only_confirmed_reservation_without_session_can_check_in(): void
    {
        $staff = $this->user('staff');
        foreach ([ReservationStatus::Pending, ReservationStatus::Rejected, ReservationStatus::NoShow, ReservationStatus::CheckedIn] as $status) {
            $reservation = $this->reservation($status);
            $this->actingAs($staff)->post(route('pos.reservations.check-in', $reservation), ['table_id' => $this->table(4)->id])
                ->assertSessionHasErrors('reservation');
        }

        $reservation = $this->reservation(ReservationStatus::Confirmed);
        $table = $this->table(4);
        $this->post(route('pos.reservations.check-in', $reservation), ['table_id' => $table->id])->assertRedirect();
        $this->post(route('pos.reservations.check-in', $reservation), ['table_id' => $this->table(4)->id])->assertSessionHasErrors('reservation');
        $this->assertSame(1, DiningSession::query()->where('reservation_id', $reservation->id)->count());
    }

    public function test_check_in_rejects_every_unusable_or_too_small_table(): void
    {
        $staff = $this->user('staff');
        foreach ([RestaurantTableStatus::Cleaning, RestaurantTableStatus::Occupied, RestaurantTableStatus::Reserved] as $status) {
            $this->actingAs($staff)->post(route('pos.reservations.check-in', $this->reservation(ReservationStatus::Confirmed, 4)), [
                'table_id' => $this->table(8, $status)->id,
            ])->assertSessionHasErrors('table');
        }
        $this->post(route('pos.reservations.check-in', $this->reservation(ReservationStatus::Confirmed, 6)), ['table_id' => $this->table(4)->id])->assertSessionHasErrors('table');
        $this->post(route('pos.reservations.check-in', $this->reservation()), ['table_id' => $this->table(8, RestaurantTableStatus::Available, false)->id])->assertSessionHasErrors('table');

        $deleted = $this->table(8);
        $deleted->delete();
        $this->post(route('pos.reservations.check-in', $this->reservation()), ['table_id' => $deleted->id])->assertSessionHasErrors('table_id');
    }

    public function test_active_session_blocks_check_in_and_walk_in_on_same_table(): void
    {
        $staff = $this->user('staff');
        $table = $this->table(8);
        $existing = $this->diningSession($table, $staff->employee);

        $this->actingAs($staff)->post(route('pos.reservations.check-in', $this->reservation()), ['table_id' => $table->id])->assertSessionHasErrors('table');
        $this->post(route('pos.dining-sessions.store', $table), ['guest_count' => 2])->assertSessionHasErrors('table');
        $this->assertDatabaseCount('dining_sessions', 1);
        $this->assertDatabaseHas('dining_sessions', ['id' => $existing->id]);

        $freeTable = $this->table(8);
        $firstReservation = $this->reservation();
        $secondReservation = $this->reservation();
        $this->post(route('pos.reservations.check-in', $firstReservation), ['table_id' => $freeTable->id])->assertRedirect();
        $this->post(route('pos.reservations.check-in', $secondReservation), ['table_id' => $freeTable->id])->assertSessionHasErrors('table');
        $this->assertSame(ReservationStatus::Confirmed, $secondReservation->fresh()->status);
    }

    public function test_walk_in_supports_anonymous_or_existing_customer_without_reservation(): void
    {
        $staff = $this->user('staff');
        $anonymousTable = $this->table(4);
        $this->actingAs($staff)->post(route('pos.dining-sessions.store', $anonymousTable), ['guest_count' => 3, 'note' => 'Window'])
            ->assertRedirect();
        $anonymous = DiningSession::query()->where('table_id', $anonymousTable->id)->sole();
        $this->assertNull($anonymous->customer_id);
        $this->assertNull($anonymous->reservation_id);
        $this->assertSame('Window', $anonymous->note);

        $customer = Customer::query()->forceCreate(['name' => 'Known Customer', 'phone' => '0901']);
        $knownTable = $this->table(6);
        $this->post(route('pos.dining-sessions.store', $knownTable), ['guest_count' => 2, 'customer_id' => $customer->id])->assertRedirect();
        $this->assertDatabaseHas('dining_sessions', ['table_id' => $knownTable->id, 'customer_id' => $customer->id, 'reservation_id' => null]);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_each_action_requires_its_independent_permissions_and_internal_context(): void
    {
        $table = $this->table(6);
        $reservation = $this->reservation();
        $this->get(route('pos.dining-sessions.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user('kitchen'))->get(route('pos.dining-sessions.index'))->assertForbidden();
        $this->actingAs($this->user('customer', false))->get(route('pos.dining-sessions.index'))->assertForbidden();

        foreach (['staff', 'manager', 'admin'] as $role) {
            $this->actingAs($this->user($role))->get(route('pos.dining-sessions.index'))->assertOk();
        }

        $staff = $this->user('staff');
        $session = $this->diningSession($this->table(4), $staff->employee);
        foreach (['reservation.manage', 'dining-session.open', 'table.operate'] as $code) {
            $permission = Permission::where('code', $code)->firstOrFail();
            $staff->role->permissions()->detach($permission);
            $this->actingAs($staff)->post(route('pos.reservations.check-in', $reservation), ['table_id' => $table->id])->assertForbidden();
            if ($code !== 'reservation.manage') {
                $this->post(route('pos.dining-sessions.store', $table), ['guest_count' => 2])->assertForbidden();
            }
            $staff->role->permissions()->attach($permission);
        }
        $staff->role->permissions()->detach(Permission::where('code', 'dining-session.view')->firstOrFail());
        $this->get(route('pos.dining-sessions.index'))->assertForbidden();
        $this->get(route('pos.dining-sessions.show', $session))->assertForbidden();
    }

    public function test_client_cannot_forge_session_owned_fields_or_deleted_customer(): void
    {
        $staff = $this->user('staff');
        $table = $this->table(6);
        $payload = ['guest_count' => 2, 'table_id' => 999, 'status' => 'completed', 'reservation_id' => 1,
            'opened_by_employee_id' => 999, 'session_code' => 'FORGED', 'started_at' => now(), 'ended_at' => now(),
            'completed_by_employee_id' => 999];
        $this->actingAs($staff)->post(route('pos.dining-sessions.store', $table), $payload)
            ->assertSessionHasErrors(['table_id', 'status', 'reservation_id', 'opened_by_employee_id', 'session_code', 'started_at', 'ended_at', 'completed_by_employee_id']);
        $this->assertDatabaseCount('dining_sessions', 0);

        $reservation = $this->reservation();
        $this->post(route('pos.reservations.check-in', $reservation), ['table_id' => $table->id] + $payload + ['customer_id' => 999])
            ->assertSessionHasErrors(['status', 'reservation_id', 'customer_id', 'opened_by_employee_id', 'session_code', 'started_at', 'ended_at', 'completed_by_employee_id']);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);

        $customer = Customer::query()->forceCreate(['name' => 'Deleted']);
        $customer->delete();
        $this->post(route('pos.dining-sessions.store', $table), ['guest_count' => 2, 'customer_id' => $customer->id])->assertSessionHasErrors('customer_id');
    }

    public function test_session_and_table_changes_roll_back_on_insert_or_table_update_failure(): void
    {
        $staff = $this->user('staff');
        $table = $this->table(6);
        Event::listen('eloquent.creating: '.DiningSession::class, fn () => throw new RuntimeException('insert failed'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($staff)->post(route('pos.dining-sessions.store', $table), ['guest_count' => 2]);
            $this->fail('Expected insert failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('insert failed', $exception->getMessage());
        }
        $this->assertSame(RestaurantTableStatus::Available, $table->fresh()->runtime_status);
        $this->assertDatabaseCount('dining_sessions', 0);

        Event::forget('eloquent.creating: '.DiningSession::class);
        Event::listen('eloquent.saving: '.RestaurantTable::class, function (RestaurantTable $saving): void {
            if ($saving->isDirty('runtime_status')) {
                throw new RuntimeException('table failed');
            }
        });
        try {
            $this->post(route('pos.dining-sessions.store', $table), ['guest_count' => 2]);
            $this->fail('Expected table failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('table failed', $exception->getMessage());
        }
        $this->assertSame(RestaurantTableStatus::Available, $table->fresh()->runtime_status);
        $this->assertDatabaseCount('dining_sessions', 0);
    }

    public function test_check_in_rolls_back_reservation_session_and_table_when_table_update_fails(): void
    {
        $staff = $this->user('staff');
        $reservation = $this->reservation();
        $table = $this->table(6);
        Event::listen('eloquent.saving: '.RestaurantTable::class, function (RestaurantTable $saving): void {
            if ($saving->isDirty('runtime_status')) {
                throw new RuntimeException('table failed');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($staff)->post(route('pos.reservations.check-in', $reservation), ['table_id' => $table->id]);
            $this->fail('Expected table failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('table failed', $exception->getMessage());
        }

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertNull($reservation->table_id);
        $this->assertNull($reservation->checked_in_at);
        $this->assertSame(RestaurantTableStatus::Available, $table->fresh()->runtime_status);
        $this->assertDatabaseCount('dining_sessions', 0);
    }

    public function test_table_map_reservation_and_session_pages_render_links(): void
    {
        $staff = $this->user('staff');
        $reservation = $this->reservation();
        $table = $this->table(6);
        $this->actingAs($staff)->post(route('pos.reservations.check-in', $reservation), ['table_id' => $table->id]);
        $session = DiningSession::query()->sole();

        $this->get(route('pos.tables.index'))->assertOk()->assertSee(route('pos.dining-sessions.show', $session));
        $this->get(route('pos.reservations.show', $reservation))->assertOk()->assertSee(route('pos.dining-sessions.show', $session));
        $this->get(route('pos.dining-sessions.index'))->assertOk()->assertSee($session->session_code);
        $this->get(route('pos.dining-sessions.show', $session))->assertOk()->assertSee($reservation->reservation_code);
        $freeTable = $this->table(6);
        $this->get(route('pos.dining-sessions.create', $freeTable))->assertOk()->assertSee(route('pos.dining-sessions.store', $freeTable));
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        if ($employee) {
            Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Employee', 'status' => EmployeeStatus::Active]);
        }

        return $user;
    }

    private function table(int $capacity, RestaurantTableStatus $status = RestaurantTableStatus::Available, bool $active = true): RestaurantTable
    {
        return RestaurantTable::query()->forceCreate(['code' => 'T-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Table', 'capacity' => $capacity, 'runtime_status' => $status, 'is_active' => $active]);
    }

    private function reservation(ReservationStatus $status = ReservationStatus::Confirmed, int $partySize = 4): Reservation
    {
        $customer = Customer::query()->forceCreate(['name' => 'Customer']);

        return Reservation::query()->forceCreate(['customer_id' => $customer->id, 'reservation_code' => 'RSV-'.fake()->unique()->numberBetween(1, 999999), 'reservation_date' => now()->toDateString(), 'reservation_time' => '18:00', 'party_size' => $partySize, 'status' => $status]);
    }

    private function diningSession(RestaurantTable $table, Employee $employee): DiningSession
    {
        return DiningSession::query()->forceCreate(['session_code' => 'DS-'.fake()->unique()->numberBetween(1, 999999), 'table_id' => $table->id, 'opened_by_employee_id' => $employee->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 2]);
    }
}
