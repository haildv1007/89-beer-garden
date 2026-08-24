<?php

namespace Tests\Feature\Customer;

use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class ReservationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_render_form_and_create_pending_reservation_without_selecting_table(): void
    {
        $this->get(route('customer.reservations.create'))->assertOk()->assertViewIs('customer.reservations.create');
        $payload = $this->payload(['note' => 'Private request note']);
        $response = $this->post(route('customer.reservations.store'), $payload);

        $reservation = Reservation::firstOrFail();
        $customer = Customer::firstOrFail();
        $response->assertRedirect(route('customer.reservations.confirmation', absolute: false));
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertStringStartsWith('RSV-', $reservation->reservation_code);
        $this->assertNull($reservation->table_id);
        $this->assertNull($reservation->confirmed_by_employee_id);
        $this->assertSame($customer->id, $reservation->customer_id);
        $this->assertSame($payload['name'], $customer->name);
        $this->assertSame($payload['phone'], $customer->phone);

        $this->get(route('customer.reservations.confirmation'))
            ->assertOk()->assertSee($reservation->reservation_code)->assertDontSee('Private request note');
        $this->get(route('customer.reservations.confirmation'))->assertNotFound();
    }

    public function test_logged_in_customer_uses_exactly_their_own_profile(): void
    {
        [$user, $customer] = $this->account('owner@example.test');

        $this->actingAs($user)->post(route('customer.reservations.store'), $this->payload([
            'name' => 'Updated Contact', 'phone' => '0911000000',
        ]))->assertRedirect();

        $reservation = Reservation::firstOrFail();
        $this->assertSame($customer->id, $reservation->customer_id);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Contact', 'phone' => '0911000000']);
    }

    public function test_invalid_or_past_input_does_not_create_partial_customer_or_reservation(): void
    {
        $this->post(route('customer.reservations.store'), [
            'name' => '', 'phone' => '', 'reservation_date' => now()->subDay()->toDateString(),
            'reservation_time' => now()->format('H:i'), 'party_size' => 0,
        ])->assertSessionHasErrors(['name', 'phone', 'reservation_date', 'party_size']);

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_guest_customer_creation_rolls_back_when_reservation_insert_fails(): void
    {
        Event::listen('eloquent.creating: '.Reservation::class, fn () => throw new RuntimeException('Reservation insert failed.'));
        $this->withoutExceptionHandling();

        try {
            $this->post(route('customer.reservations.store'), $this->payload());
            $this->fail('Expected reservation insert failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Reservation insert failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_client_cannot_forge_server_owned_reservation_fields(): void
    {
        $payload = $this->payload() + [
            'status' => 'confirmed', 'table_id' => 1, 'customer_id' => 1,
            'confirmed_by_employee_id' => 1, 'confirmed_at' => now(), 'checked_in_at' => now(),
            'completed_at' => now(), 'no_show_at' => now(), 'cancelled_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ];

        $this->post(route('customer.reservations.store'), $payload)->assertSessionHasErrors([
            'status', 'table_id', 'customer_id', 'confirmed_by_employee_id', 'confirmed_at',
            'checked_in_at', 'completed_at', 'no_show_at', 'cancelled_at', 'created_at', 'updated_at',
        ]);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_customer_can_render_only_own_list_and_detail_without_internal_note(): void
    {
        [$owner, $customer] = $this->account('owner@example.test');
        [, $otherCustomer] = $this->account('other@example.test');
        $own = $this->reservation($customer, 'RSV-OWN', 'Internal note secret');
        $other = $this->reservation($otherCustomer, 'RSV-OTHER');

        $this->actingAs($owner)->get(route('customer.reservations.index'))
            ->assertOk()->assertSee('RSV-OWN')->assertDontSee('RSV-OTHER');
        $this->get(route('customer.reservations.show', $own))
            ->assertOk()->assertSee('RSV-OWN')->assertDontSee('Internal note secret');
        $this->get(route('customer.reservations.show', $other))->assertNotFound();
        auth()->logout();
        $this->get(route('customer.reservations.show', $own))->assertRedirect(route('login'));
    }

    public function test_internal_authenticated_user_cannot_use_public_customer_reservation_flow(): void
    {
        $staff = User::factory()->forRole(Role::where('code', 'staff')->firstOrFail())->create();
        $this->actingAs($staff)->get(route('customer.reservations.create'))->assertForbidden();
        $this->post(route('customer.reservations.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('reservations', 0);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Guest Customer', 'phone' => '0901000000',
            'reservation_date' => now()->addDay()->toDateString(), 'reservation_time' => '18:30',
            'party_size' => 4, 'note' => null,
        ], $overrides);
    }

    /** @return array{User, Customer} */
    private function account(string $email): array
    {
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create(['email' => $email]);
        $customer = Customer::query()->forceCreate(['user_id' => $user->id, 'name' => $email, 'phone' => '0900000000']);

        return [$user, $customer];
    }

    private function reservation(Customer $customer, string $code, ?string $note = null): Reservation
    {
        return Reservation::query()->forceCreate([
            'customer_id' => $customer->id, 'reservation_code' => $code,
            'reservation_date' => now()->addDay()->toDateString(), 'reservation_time' => '18:30',
            'party_size' => 2, 'status' => ReservationStatus::Pending, 'note' => $note,
        ]);
    }
}
