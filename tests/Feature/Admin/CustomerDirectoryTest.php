<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_directory_requires_admin_context_active_employee_and_customer_view(): void
    {
        $customer = Customer::query()->forceCreate(['name' => 'Walk-in']);
        $this->get(route('admin.customers.index'))->assertRedirect(route('login'));

        foreach (['staff', 'kitchen', 'customer'] as $role) {
            $this->actingAs($this->user($role, $role !== 'customer'))->get(route('admin.customers.index'))->assertForbidden();
        }

        $manager = $this->user('manager', true);
        $admin = $this->user('admin', true);
        $this->actingAs($manager)->get(route('admin.customers.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertOk();

        $manager->role->permissions()->detach(Permission::where('code', 'customer.view')->firstOrFail());
        $this->actingAs($manager)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('admin.customers.index'))->assertForbidden();
    }

    public function test_search_name_phone_customer_email_and_linked_user_email(): void
    {
        $manager = $this->user('manager', true);
        Customer::query()->forceCreate(['name' => 'Lan Nguyen', 'phone' => '0901234567', 'email' => 'lan@profile.test']);
        $linkedUser = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create(['email' => 'account@example.test']);
        Customer::query()->forceCreate(['user_id' => $linkedUser->id, 'name' => 'Linked Customer', 'email' => null]);
        Customer::query()->forceCreate(['name' => 'Hidden Result', 'phone' => '0999999999']);

        foreach (['Lan Nguyen', '090123', 'lan@profile.test', 'account@example.test'] as $search) {
            $this->actingAs($manager)->get(route('admin.customers.index', ['q' => $search]))
                ->assertOk()->assertDontSee('Hidden Result');
        }
    }

    public function test_directory_paginates_shows_accountless_profile_and_excludes_soft_deleted_customer(): void
    {
        $manager = $this->user('manager', true);
        for ($index = 1; $index <= 21; $index++) {
            Customer::query()->forceCreate(['name' => sprintf('Customer %02d', $index)]);
        }
        $deleted = Customer::query()->forceCreate(['name' => 'Deleted Secret']);
        $deleted->delete();

        $this->actingAs($manager)->get(route('admin.customers.index'))
            ->assertOk()->assertSee(__('customer.admin.guest_profile'))->assertDontSee('Deleted Secret');
        $this->get(route('admin.customers.index', ['page' => 2]))->assertOk()->assertSee('Customer 21');
    }

    public function test_detail_is_read_only_and_never_exposes_password_hash(): void
    {
        $admin = $this->user('admin', true);
        $linkedUser = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create(['email' => 'linked@example.test']);
        $customer = Customer::query()->forceCreate(['user_id' => $linkedUser->id, 'name' => 'Linked', 'email' => 'linked@example.test']);

        $this->actingAs($admin)->get(route('admin.customers.show', $customer))
            ->assertOk()->assertSee('linked@example.test')->assertDontSee($linkedUser->password);
        $this->assertFalse(Route::has('admin.customers.edit'));
        $this->assertFalse(Route::has('admin.customers.destroy'));
    }

    private function user(string $role, bool $employee = false): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        if ($employee) {
            Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'E-'.$user->id.'-'.fake()->unique()->numberBetween(1, 99999), 'name' => 'Employee', 'status' => EmployeeStatus::Active]);
        }

        return $user;
    }
}
