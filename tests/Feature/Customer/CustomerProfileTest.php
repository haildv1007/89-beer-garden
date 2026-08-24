<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

        $this->actingAs($owner)->get(route('customer.profile.show', $profile))->assertOk()->assertSee($profile->name);
        $this->get(route('customer.profile.show', $other))->assertNotFound();
        $this->get(route('customer.profile.edit', $other))->assertNotFound();
    }

    public function test_customer_can_update_allowed_fields_and_email_is_synchronized_atomically(): void
    {
        [$user, $profile] = $this->account('old@example.com');

        $this->actingAs($user)->put(route('customer.profile.update', $profile), [
            'name' => 'Updated Name', 'phone' => '0911222333', 'email' => ' NEW@EXAMPLE.COM ',
        ])->assertRedirect(route('customer.profile.show', $profile));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
        $this->assertDatabaseHas('customers', ['id' => $profile->id, 'name' => 'Updated Name', 'phone' => '0911222333', 'email' => 'new@example.com']);
    }

    public function test_profile_update_rejects_protected_fields_without_partial_change(): void
    {
        [$user, $profile] = $this->account('safe@example.com');
        $originalPassword = $user->password;
        $otherUser = User::factory()->forRole(Role::where('code', 'admin')->firstOrFail())->create();

        $this->actingAs($user)->put(route('customer.profile.update', $profile), [
            'name' => 'Forged', 'phone' => '000', 'email' => 'forged@example.com',
            'user_id' => $otherUser->id, 'role_id' => $otherUser->role_id, 'password' => 'Changed#Password123',
            'note' => 'overwrite internal note', 'status' => User::STATUS_DISABLED,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => now(),
        ])->assertSessionHasErrors(['user_id', 'role_id', 'password', 'note', 'status', 'created_at', 'updated_at', 'deleted_at']);

        $this->assertSame('safe@example.com', $user->fresh()->email);
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertSame('Customer safe@example.com', $profile->fresh()->name);
        $this->assertNull($profile->fresh()->note);
    }

    public function test_duplicate_email_is_rejected_and_transaction_rolls_back_if_customer_save_fails(): void
    {
        [$user, $profile] = $this->account('original@example.com');
        $this->account('taken@example.com');

        $this->actingAs($user)->put(route('customer.profile.update', $profile), [
            'name' => 'Duplicate', 'phone' => null, 'email' => 'TAKEN@EXAMPLE.COM',
        ])->assertSessionHasErrors('email');
        $this->assertSame('original@example.com', $user->fresh()->email);

        Event::listen('eloquent.saving: '.Customer::class, function (Customer $saving): void {
            if ($saving->isDirty('name')) {
                throw new RuntimeException('Profile save failed.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->put(route('customer.profile.update', $profile), [
                'name' => 'Will Roll Back', 'phone' => '0999', 'email' => 'rollback@example.com',
            ]);
            $this->fail('Expected profile update to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Profile save failed.', $exception->getMessage());
        }

        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame('original@example.com', $profile->fresh()->email);
    }

    public function test_missing_profile_and_revoked_permission_fail_closed(): void
    {
        $orphan = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();
        [, $profile] = $this->account('target@example.com');
        $this->actingAs($orphan)->get(route('customer.profile.show', $profile))->assertNotFound();

        [$owner, $owned] = $this->account('revoked@example.com');
        $owner->role->permissions()->detach(Permission::where('code', 'customer.profile.manage-own')->firstOrFail());
        $this->actingAs($owner)->get(route('customer.profile.show', $owned))->assertNotFound();
        $this->put(route('customer.profile.update', $owned), ['name' => 'No', 'phone' => null, 'email' => 'no@example.com'])->assertNotFound();
    }

    public function test_customer_cannot_access_admin_customer_directory(): void
    {
        [$user] = $this->account('customer@example.com');
        $this->actingAs($user)->get(route('admin.customers.index'))->assertForbidden();
    }

    /** @return array{User, Customer} */
    private function account(string $email): array
    {
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create(['email' => $email]);
        $customer = Customer::query()->forceCreate(['user_id' => $user->id, 'name' => 'Customer '.$email, 'email' => $email]);

        return [$user, $customer];
    }
}
