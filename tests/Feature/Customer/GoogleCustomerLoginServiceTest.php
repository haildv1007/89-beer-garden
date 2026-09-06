<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\Customer\GoogleCustomerLoginService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->get(route('customer.profile.show', $user->fresh()->customer))
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
}
