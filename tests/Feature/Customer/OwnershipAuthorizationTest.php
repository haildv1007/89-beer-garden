<?php

namespace Tests\Feature\Customer;

use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OwnershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_customer_can_access_only_their_own_profile_and_reservations(): void
    {
        [$owner, $ownCustomer] = $this->createCustomerAccount('owner@example.test');
        [, $otherCustomer] = $this->createCustomerAccount('other@example.test');
        $ownReservation = $this->createReservation($ownCustomer, 'R-OWN');
        $otherReservation = $this->createReservation($otherCustomer, 'R-OTHER');

        $this->assertTrue(Gate::forUser($owner)->allows('viewOwn', $ownCustomer));
        $this->assertTrue(Gate::forUser($owner)->allows('updateOwn', $ownCustomer));
        $this->assertTrue(Gate::forUser($owner)->allows('viewOwn', $ownReservation));

        $this->assertSame(404, Gate::forUser($owner)->inspect('viewOwn', $otherCustomer)->status());
        $this->assertSame(404, Gate::forUser($owner)->inspect('updateOwn', $otherCustomer)->status());
        $this->assertSame(404, Gate::forUser($owner)->inspect('viewOwn', $otherReservation)->status());
    }

    public function test_guest_and_internal_management_permission_do_not_gain_customer_ownership(): void
    {
        [, $customer] = $this->createCustomerAccount('customer@example.test');
        $reservation = $this->createReservation($customer, 'R-CUSTOMER');
        $admin = User::factory()
            ->forRole(Role::query()->where('code', 'admin')->firstOrFail())
            ->create();

        $this->assertFalse(Gate::forUser(null)->allows('viewOwn', $customer));
        $this->assertFalse(Gate::forUser(null)->allows('viewOwn', $reservation));
        $this->assertTrue($admin->can('customer.view'));
        $this->assertSame(404, Gate::forUser($admin)->inspect('viewOwn', $customer)->status());
        $this->assertSame(404, Gate::forUser($admin)->inspect('viewOwn', $reservation)->status());
    }

    public function test_ownership_permission_revocation_is_enforced_without_exposing_the_resource(): void
    {
        [$user, $customer] = $this->createCustomerAccount('revoked@example.test');

        $user->role
            ->permissions()
            ->detach(Permission::query()->where('code', 'customer.profile.manage-own')->firstOrFail());

        $this->assertSame(404, Gate::forUser($user)->inspect('viewOwn', $customer)->status());
    }

    /** @return array{User, Customer} */
    private function createCustomerAccount(string $email): array
    {
        $user = User::factory()
            ->forRole(Role::query()->where('code', 'customer')->firstOrFail())
            ->create(['email' => $email]);
        $customer = Customer::forceCreate([
            'user_id' => $user->getKey(),
            'name' => $email,
        ]);

        return [$user, $customer];
    }

    private function createReservation(Customer $customer, string $code): Reservation
    {
        return Reservation::forceCreate([
            'customer_id' => $customer->getKey(),
            'reservation_code' => $code,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:00',
            'party_size' => 2,
            'status' => ReservationStatus::Pending,
        ]);
    }
}
