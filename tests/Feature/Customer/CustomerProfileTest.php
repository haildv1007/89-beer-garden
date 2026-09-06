<?php

namespace Tests\Feature\Customer;

use App\Enums\EmployeeStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_customer_can_view_only_their_own_profile(): void
    {
        [$owner, $profile] = $this->account('owner@example.com');
        [, $other] = $this->account('other@example.com');

        $this->actingAs($owner)->get(route('customer.profile.show'))->assertOk()->assertSee($profile->name);
        $this->get('/profile/'.$other->id)->assertRedirect(route('customer.profile.show'));
        $this->get('/profile/'.$other->id.'/edit')->assertRedirect(route('customer.profile.edit'));
    }

    public function test_customer_can_update_allowed_fields_and_email_is_synchronized_atomically(): void
    {
        [$user, $profile] = $this->account('old@example.com');

        $this->actingAs($user)
            ->put(route('customer.profile.update'), [
                'name' => 'Updated Name',
                'phone' => '0911222333',
                'email' => ' NEW@EXAMPLE.COM ',
            ])
            ->assertRedirect(route('customer.profile.show'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
        $this->assertDatabaseHas('customers', [
            'id' => $profile->id,
            'name' => 'Updated Name',
            'phone' => '0911222333',
            'email' => 'new@example.com',
        ]);
    }

    public function test_customer_avatar_is_stored_and_rendered_in_customer_and_admin_profiles(): void
    {
        Storage::fake('public');
        [$user, $profile] = $this->account('avatar@example.com');

        $this->actingAs($user)
            ->put(route('customer.profile.update'), [
                'name' => $profile->name,
                'phone' => null,
                'email' => $profile->email,
                'avatar' => UploadedFile::fake()->createWithContent(
                    'avatar.jpg',
                    file_get_contents(public_path('images/brand/atmosphere-evening.jpg')),
                ),
            ])
            ->assertRedirect(route('customer.profile.show'));

        $path = $profile->fresh()->avatar_path;
        Storage::disk('public')->assertExists($path);
        $this->get(route('customer.profile.show'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($path));

        $admin = User::factory()
            ->forRole(Role::where('code', 'admin')->firstOrFail())
            ->create();
        Employee::forceCreate([
            'user_id' => $admin->id,
            'employee_code' => 'AVATAR-ADMIN',
            'name' => 'Admin',
            'status' => EmployeeStatus::Active,
        ]);
        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($path));
        $this->get(route('admin.customers.show', $profile))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($path));
    }

    public function test_profile_update_rejects_protected_fields_without_partial_change(): void
    {
        [$user, $profile] = $this->account('safe@example.com');
        $originalPassword = $user->password;
        $otherUser = User::factory()
            ->forRole(Role::where('code', 'admin')->firstOrFail())
            ->create();

        $this->actingAs($user)
            ->put(route('customer.profile.update'), [
                'name' => 'Forged',
                'phone' => '000',
                'email' => 'forged@example.com',
                'user_id' => $otherUser->id,
                'role_id' => $otherUser->role_id,
                'password' => 'Changed#Password123',
                'note' => 'overwrite internal note',
                'status' => User::STATUS_DISABLED,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => now(),
            ])
            ->assertSessionHasErrors([
                'user_id',
                'role_id',
                'password',
                'note',
                'status',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

        $this->assertSame('safe@example.com', $user->fresh()->email);
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertSame('Customer safe@example.com', $profile->fresh()->name);
        $this->assertNull($profile->fresh()->note);
    }

    public function test_duplicate_email_is_rejected_and_transaction_rolls_back_if_customer_save_fails(): void
    {
        [$user, $profile] = $this->account('original@example.com');
        $this->account('taken@example.com');

        $this->actingAs($user)
            ->put(route('customer.profile.update'), [
                'name' => 'Duplicate',
                'phone' => null,
                'email' => 'TAKEN@EXAMPLE.COM',
            ])
            ->assertSessionHasErrors('email');
        $this->assertSame('original@example.com', $user->fresh()->email);

        Event::listen('eloquent.saving: '.Customer::class, function (Customer $saving): void {
            if ($saving->isDirty('name')) {
                throw new RuntimeException('Profile save failed.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->put(route('customer.profile.update'), [
                'name' => 'Will Roll Back',
                'phone' => '0999',
                'email' => 'rollback@example.com',
            ]);
            $this->fail('Expected profile update to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Profile save failed.', $exception->getMessage());
        }

        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame('original@example.com', $profile->fresh()->email);
    }

    public function test_missing_profile_is_created_for_the_authenticated_owner(): void
    {
        $orphan = User::factory()
            ->forRole(Role::where('code', 'customer')->firstOrFail())
            ->create();
        [, $profile] = $this->account('target@example.com');
        $this->actingAs($orphan)->get(route('customer.profile.show'))->assertOk();
        $this->assertDatabaseHas('customers', ['user_id' => $orphan->id]);

        [$owner, $owned] = $this->account('profile-owner@example.com');
        $owner->role->permissions()->detach(Permission::where('code', 'customer.profile.manage-own')->firstOrFail());
        $this->actingAs($owner)->get(route('customer.profile.show'))->assertOk();
        $this->put(route('customer.profile.update'), [
            'name' => 'Updated owner',
            'phone' => null,
            'email' => 'profile-owner@example.com',
        ])->assertRedirect(route('customer.profile.show'));
    }

    public function test_customer_cannot_access_admin_customer_directory(): void
    {
        [$user] = $this->account('customer@example.com');
        $this->actingAs($user)->get(route('admin.customers.index'))->assertForbidden();
    }

    /** @return array{User, Customer} */
    private function account(string $email): array
    {
        $user = User::factory()
            ->forRole(Role::where('code', 'customer')->firstOrFail())
            ->create(['email' => $email]);
        $customer = Customer::query()->forceCreate([
            'user_id' => $user->id,
            'name' => 'Customer '.$email,
            'email' => $email,
        ]);

        return [$user, $customer];
    }
}
