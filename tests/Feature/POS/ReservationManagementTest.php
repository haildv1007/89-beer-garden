<?php

namespace Tests\Feature\POS;

use App\Enums\EmployeeStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_internal_pages_require_context_active_employee_and_reservation_manage(): void
    {
        $reservation = $this->reservation();
        $this->get(route('pos.reservations.index'))->assertRedirect(route('login'));

        $this->actingAs($this->user('kitchen', true))->get(route('pos.reservations.index'))->assertForbidden();
        $this->actingAs($this->user('customer'))->get(route('pos.reservations.index'))->assertForbidden();

        foreach (['staff', 'manager', 'admin'] as $role) {
            $this->actingAs($this->user($role, true))
                ->get(route('pos.reservations.index'))
                ->assertOk()
                ->assertSee($reservation->reservation_code);
        }

        $staff = $this->user('staff', true);
        $staff->role->permissions()->detach(Permission::where('code', 'reservation.manage')->firstOrFail());
        $this->actingAs($staff)->get(route('pos.reservations.index'))->assertForbidden();
        $this->get(route('pos.home'))->assertOk()->assertDontSee(route('pos.reservations.index'));
    }

    public function test_list_search_filter_and_detail_blades_render(): void
    {
        $staff = $this->user('staff', true);
        $match = $this->reservation(ReservationStatus::Pending, ['reservation_code' => 'RSV-MATCH']);
        $match->customer->forceFill(['name' => 'Lan Search', 'phone' => '0901234567'])->save();
        $this->reservation(ReservationStatus::Rejected, [
            'reservation_code' => 'RSV-HIDDEN',
            'reservation_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($staff)
            ->get(
                route('pos.reservations.index', [
                    'q' => '090123',
                    'status' => 'pending',
                    'date_from' => now()->toDateString(),
                    'date_to' => now()->addDays(2)->toDateString(),
                ]),
            )
            ->assertOk()
            ->assertSee('RSV-MATCH')
            ->assertDontSee('RSV-HIDDEN');
        $this->get(route('pos.reservations.index', ['date_to' => now()->addDay()->toDateString()]))->assertOk();
        $this->get(route('pos.reservations.show', $match))->assertOk()->assertSee('Lan Search');
    }

    public function test_admin_reservation_pages_keep_the_admin_navigation_context(): void
    {
        $admin = $this->user('admin', true);
        $reservation = $this->reservation(ReservationStatus::Pending, [
            'reservation_code' => 'RSV-01M1669JCHV64XMH26XBHBVGZC',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee(route('admin.reservations.show', $reservation))
            ->assertSee('RSV-XBHBVGZC')
            ->assertSee($reservation->reservation_code)
            ->assertDontSee('operation-header');

        $this->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee(route('admin.reservations.confirm', $reservation))
            ->assertSee(route('admin.reservations.reject', $reservation));
    }

    public function test_confirm_requires_pending_capacity_and_records_server_owned_audit_without_assigning_table(): void
    {
        $staff = $this->user('staff', true);
        $reservation = $this->reservation(ReservationStatus::Pending, ['party_size' => 6]);
        $table = $this->table(6);

        $this->actingAs($staff)
            ->patch(route('pos.reservations.confirm', $reservation), [
                'status' => 'completed',
                'table_id' => $table->id,
                'confirmed_by_employee_id' => 999,
            ])
            ->assertSessionHasErrors(['status', 'table_id', 'confirmed_by_employee_id']);
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);

        $this->patch(route('pos.reservations.confirm', $reservation))->assertRedirect();
        $confirmed = $reservation->fresh();
        $this->assertSame(ReservationStatus::Confirmed, $confirmed->status);
        $this->assertSame($staff->employee->id, $confirmed->confirmed_by_employee_id);
        $this->assertNotNull($confirmed->confirmed_at);
        $this->assertNull($confirmed->table_id);
        $this->assertSame(RestaurantTableStatus::Available, $table->fresh()->runtime_status);
        $this->assertDatabaseCount('dining_sessions', 0);

        $confirmedAt = $confirmed->confirmed_at;
        $this->patch(route('pos.reservations.confirm', $reservation))->assertSessionHasErrors('reservation');
        $this->assertTrue($confirmedAt->equalTo($reservation->fresh()->confirmed_at));
    }

    public function test_pending_reservation_can_be_updated_over_ajax_without_confirming_it(): void
    {
        $staff = $this->user('staff', true);
        $reservation = $this->reservation();

        $this->actingAs($staff)
            ->putJson(route('pos.reservations.update', $reservation), [
                'name' => 'Khách đã sửa',
                'phone' => '0987654321',
                'reservation_date' => now()->addDays(2)->toDateString(),
                'reservation_time' => '20:15',
                'party_size' => 9,
                'note' => 'Khách đổi giờ',
                'items' => [],
            ])
            ->assertOk()
            ->assertJson(['message' => 'Đã cập nhật đơn đặt bàn.']);

        $reservation->refresh();
        $this->assertSame(9, $reservation->party_size);
        $this->assertSame('20:15', substr($reservation->reservation_time, 0, 5));
        $this->assertSame('Khách đổi giờ', $reservation->note);
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertSame('Khách đã sửa', $reservation->customer->name);
        $this->assertSame('0987654321', $reservation->customer->phone);
    }

    public function test_changing_to_an_existing_phone_reassigns_the_reservation_to_that_customer(): void
    {
        $staff = $this->user('staff', true);
        $reservation = $this->reservation();
        $existing = Customer::query()->forceCreate(['name' => 'Khách cũ', 'phone' => '0911222333']);

        $this->actingAs($staff)
            ->putJson(route('pos.reservations.update', $reservation), [
                'name' => 'Khách đã nhận diện',
                'phone' => '0911222333',
                'reservation_date' => now()->addDays(2)->toDateString(),
                'reservation_time' => '19:30',
                'party_size' => 6,
                'items' => [],
            ])
            ->assertOk();

        $this->assertSame($existing->id, $reservation->fresh()->customer_id);
        $this->assertSame(1, $existing->reservations()->count());
        $this->assertSame('Khách đã nhận diện', $existing->fresh()->name);
    }

    public function test_confirm_fails_closed_without_active_table_of_sufficient_capacity(): void
    {
        $staff = $this->user('staff', true);
        $reservation = $this->reservation(ReservationStatus::Pending, ['party_size' => 8]);
        $this->table(4);
        $this->table(10, false);

        $this->actingAs($staff)
            ->patch(route('pos.reservations.confirm', $reservation))
            ->assertSessionHasErrors('reservation');
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->confirmed_at);
    }

    public function test_reject_only_accepts_pending_and_double_submit_preserves_terminal_state(): void
    {
        $staff = $this->user('staff', true);
        $reservation = $this->reservation();

        $this->actingAs($staff)->patch(route('pos.reservations.reject', $reservation))->assertRedirect();
        $this->assertSame(ReservationStatus::Rejected, $reservation->fresh()->status);
        $this->patch(route('pos.reservations.reject', $reservation))->assertSessionHasErrors('reservation');
        $this->assertSame(ReservationStatus::Rejected, $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->cancelled_at);
    }

    public function test_no_show_requires_independent_permission_valid_setting_confirmed_state_and_elapsed_timeout(): void
    {
        Carbon::setTestNow('2026-08-24 18:00:00');

        try {
            $staff = $this->user('staff', true);
            $reservation = $this->reservation(ReservationStatus::Confirmed, [
                'reservation_date' => '2026-08-24',
                'reservation_time' => '17:45',
            ]);

            $staff->role->permissions()->detach(Permission::where('code', 'reservation.mark-no-show')->firstOrFail());
            $this->actingAs($staff)->patch(route('pos.reservations.mark-no-show', $reservation))->assertForbidden();
            $staff->role->permissions()->attach(Permission::where('code', 'reservation.mark-no-show')->firstOrFail());

            $this->patch(route('pos.reservations.mark-no-show', $reservation))->assertSessionHasErrors('reservation');
            SystemSetting::query()->forceCreate([
                'key' => 'no_show_timeout_minutes',
                'value' => '30',
                'type' => 'integer',
            ]);
            $this->patch(route('pos.reservations.mark-no-show', $reservation))->assertSessionHasErrors('reservation');

            Carbon::setTestNow('2026-08-24 18:16:00');
            $this->patch(route('pos.reservations.mark-no-show', $reservation))->assertRedirect();
            $this->assertSame(ReservationStatus::NoShow, $reservation->fresh()->status);
            $this->assertNotNull($reservation->fresh()->no_show_at);

            $noShowAt = $reservation->fresh()->no_show_at;
            $this->patch(route('pos.reservations.mark-no-show', $reservation))->assertSessionHasErrors('reservation');
            $this->assertTrue($noShowAt->equalTo($reservation->fresh()->no_show_at));

            $pending = $this->reservation(ReservationStatus::Pending);
            $this->patch(route('pos.reservations.mark-no-show', $pending))->assertSessionHasErrors('reservation');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_transition_failure_rolls_back_status_and_audit_fields(): void
    {
        $staff = $this->user('staff', true);
        $reservation = $this->reservation();
        $this->table(10);
        Event::listen('eloquent.saving: '.Reservation::class, function (Reservation $saving): void {
            if ($saving->isDirty('status')) {
                throw new RuntimeException('Transition failed.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($staff)->patch(route('pos.reservations.confirm', $reservation));
            $this->fail('Expected transition failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Transition failed.', $exception->getMessage());
        }

        $reservation = $reservation->fresh();
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertNull($reservation->confirmed_at);
        $this->assertNull($reservation->confirmed_by_employee_id);
    }

    private function reservation(
        ReservationStatus $status = ReservationStatus::Pending,
        array $overrides = [],
    ): Reservation {
        $customer = Customer::query()->forceCreate([
            'name' => 'Customer '.fake()->unique()->numberBetween(1, 99999),
            'phone' => '0900000000',
        ]);

        return Reservation::query()->forceCreate(
            array_merge(
                [
                    'customer_id' => $customer->id,
                    'reservation_code' => 'RSV-'.fake()->unique()->numberBetween(1, 999999),
                    'reservation_date' => now()->addDay()->toDateString(),
                    'reservation_time' => '18:30',
                    'party_size' => 4,
                    'status' => $status,
                ],
                $overrides,
            ),
        );
    }

    private function table(int $capacity, bool $active = true): RestaurantTable
    {
        return RestaurantTable::query()->forceCreate([
            'code' => 'T-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Table',
            'capacity' => $capacity,
            'runtime_status' => RestaurantTableStatus::Available,
            'is_active' => $active,
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
