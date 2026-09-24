<?php

namespace Tests\Feature\Customer;

use App\Enums\EmployeeStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Customer\GoogleCustomerLoginService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GoogleCustomerLoginServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_google_login_creates_a_profile_for_an_existing_customer_user_without_one(): void
    {
        $user = User::factory()
            ->forRole(Role::query()->where('code', 'customer')->firstOrFail())
            ->create(['email' => 'returning@example.com', 'google_id' => null]);

        $loggedIn = app(GoogleCustomerLoginService::class)->login([
            'sub' => 'google-returning-user',
            'email' => 'RETURNING@EXAMPLE.COM',
            'email_verified' => true,
            'name' => 'Returning Guest',
        ]);

        $this->assertTrue($loggedIn->is($user));
        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'name' => 'Returning Guest',
            'email' => 'returning@example.com',
        ]);
        $this->assertSame('google-returning-user', $user->fresh()->google_id);
        $this->actingAs($user->fresh())
            ->get(route('customer.profile.show'))
            ->assertOk()
            ->assertSee('Returning Guest');
    }

    public function test_google_login_reuses_an_unclaimed_customer_profile_with_the_same_email(): void
    {
        Customer::query()->forceCreate([
            'name' => 'Khách quen',
            'email' => 'guest@example.com',
        ]);

        $user = app(GoogleCustomerLoginService::class)->login([
            'sub' => 'google-new-user',
            'email' => 'GUEST@EXAMPLE.COM',
            'email_verified' => true,
            'name' => 'Google Name',
        ]);

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame($user->id, Customer::query()->sole()->user_id);
        $this->assertSame('Khách quen', Customer::query()->sole()->name);
    }

    public function test_google_login_links_to_an_active_employee_account_with_the_same_verified_email(): void
    {
        $staff = User::factory()
            ->forRole(Role::query()->where('code', 'staff')->firstOrFail())
            ->create(['email' => 'staff@example.com', 'google_id' => null]);
        Employee::query()->forceCreate([
            'user_id' => $staff->id,
            'employee_code' => 'NV-GOOGLE-1',
            'name' => 'Google Staff',
            'status' => EmployeeStatus::Active,
        ]);

        $loggedIn = app(GoogleCustomerLoginService::class)->login([
            'sub' => 'google-staff',
            'email' => 'STAFF@EXAMPLE.COM',
            'email_verified' => true,
            'name' => 'Google Staff',
        ]);

        $this->assertTrue($loggedIn->is($staff));
        $this->assertSame('google-staff', $staff->fresh()->google_id);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_google_login_rejects_internal_account_without_an_active_employee_profile(): void
    {
        $admin = User::factory()
            ->forRole(Role::query()->where('code', 'admin')->firstOrFail())
            ->create(['email' => 'owner@example.com', 'google_id' => null]);

        try {
            app(GoogleCustomerLoginService::class)->login([
                'sub' => 'google-owner',
                'email' => 'OWNER@EXAMPLE.COM',
                'email_verified' => true,
                'name' => 'Restaurant Owner',
            ]);
            $this->fail('Expected an unlinked internal account to be rejected.');
        } catch (ValidationException) {
            $this->assertNull($admin->fresh()->google_id);
        }
    }
}
