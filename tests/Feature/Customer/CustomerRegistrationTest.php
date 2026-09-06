<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_view_registration_form(): void
    {
        $this->get(route('customer.registration.create'))
            ->assertOk()
            ->assertViewIs('customer.registration.create')
            ->assertSee('name="phone"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_valid_registration_atomically_creates_and_authenticates_customer_account(): void
    {
        $password = 'Strong#Password123';
        $response = $this->post(route('customer.registration.store'), [
            'name' => 'Lan Nguyen',
            'email' => '  LAN@EXAMPLE.COM ',
            'phone' => '0901000000',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $user = User::where('email', 'lan@example.com')->firstOrFail();
        $customer = Customer::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('customer.profile.show', $customer, false))->assertDontSee($password);
        $this->assertSame('/profile/'.$customer->id, $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role->code);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame('lan@example.com', $customer->email);
        $this->assertSame('Lan Nguyen', $customer->name);
        $this->assertSame('0901000000', $customer->phone);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_registration_links_the_existing_walk_in_profile_by_phone(): void
    {
        $existing = Customer::query()->create([
            'name' => 'Khách ghé quán',
            'phone' => '+84900000000',
            'email' => null,
        ]);

        $response = $this->post(route('customer.registration.store'), $this->validPayload('returning@example.com'));

        $user = User::query()->where('email', 'returning@example.com')->firstOrFail();
        $existing->refresh();

        $response->assertRedirect(route('customer.profile.show', $existing, false));
        $this->assertSame($user->id, $existing->user_id);
        $this->assertSame('0900000000', $existing->phone);
        $this->assertSame('returning@example.com', $existing->email);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_phone_with_an_existing_account_cannot_register_again_even_with_another_email(): void
    {
        $first = $this->post(route('customer.registration.store'), $this->validPayload('first@example.com'));
        $first->assertRedirect();
        auth()->logout();

        $payload = $this->validPayload('another@example.com');
        $payload['name'] = 'Tên khác';
        $this->post(route('customer.registration.store'), $payload)->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_name_and_email_are_optional_but_password_is_required_until_otp_exists(): void
    {
        $payload = $this->validPayload('unused@example.com');
        unset($payload['name'], $payload['email']);

        $this->post(route('customer.registration.store'), $payload)->assertRedirect();

        $user = User::query()->firstOrFail();
        $this->assertNull($user->email);
        $this->assertSame('0900000000', $user->phone);
        $this->assertSame('Khách 0000', $user->customer->name);
    }

    public function test_duplicate_email_and_invalid_password_create_no_partial_data_and_do_not_flash_password(): void
    {
        User::factory()
            ->forRole(Role::where('code', 'customer')->firstOrFail())
            ->create(['email' => 'taken@example.com']);
        $password = 'short';

        $response = $this->from(route('customer.registration.create'))
            ->post(route('customer.registration.store'), [
                'name' => 'Invalid',
                'email' => 'TAKEN@EXAMPLE.COM',
                'phone' => '0902',
                'password' => $password,
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('customer.registration.create'))
            ->assertSessionHasErrors(['email', 'password']);

        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 0);
        $this->assertGuest();
    }

    public function test_registration_rolls_back_user_when_customer_creation_fails(): void
    {
        Event::listen(
            'eloquent.creating: '.Customer::class,
            fn () => throw new RuntimeException('Customer insert failed.'),
        );
        $this->withoutExceptionHandling();

        try {
            $this->post(route('customer.registration.store'), $this->validPayload('rollback@example.com'));
            $this->fail('Expected customer creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Customer insert failed.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.com']);
        $this->assertDatabaseCount('customers', 0);
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_open_or_submit_registration(): void
    {
        $user = User::factory()
            ->forRole(Role::where('code', 'staff')->firstOrFail())
            ->create();

        $this->actingAs($user)->get(route('customer.registration.create'))->assertRedirect(route('customer.home'));
        $this->post(route('customer.registration.store'), $this->validPayload('second@example.com'))->assertRedirect(
            route('customer.home'),
        );
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
        $this->assertNull(Employee::where('user_id', $user->id)->first());
    }

    public function test_registration_rejects_forged_identity_fields(): void
    {
        $payload = $this->validPayload('forged@example.com') + [
            'role_id' => Role::where('code', 'admin')->value('id'),
            'status' => User::STATUS_DISABLED,
            'user_id' => 999,
            'note' => 'internal',
        ];

        $this->post(route('customer.registration.store'), $payload)->assertSessionHasErrors([
            'role_id',
            'status',
            'user_id',
            'note',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'forged@example.com']);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('customer.registration.store'), [])->assertSessionHasErrors(['phone', 'password']);
        }

        $this->post(route('customer.registration.store'), [])->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, string> */
    private function validPayload(string $email): array
    {
        return [
            'name' => 'Customer',
            'email' => $email,
            'phone' => '0900000000',
            'password' => 'Strong#Password123',
            'password_confirmation' => 'Strong#Password123',
        ];
    }
}
