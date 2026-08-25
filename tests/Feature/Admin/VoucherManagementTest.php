<?php

namespace Tests\Feature\Admin;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_list_search_filter_create_update_and_soft_delete_unused_voucher(): void
    {
        $manager = $this->user('manager');
        $this->actingAs($manager)->post(route('admin.vouchers.store'), $this->payload(' save10 ', 'Summer'))
            ->assertRedirect();
        $voucher = Voucher::query()->sole();
        $this->assertSame('SAVE10', $voucher->code);
        $this->assertSame(0, $voucher->used_count);
        $this->get(route('admin.vouchers.index', ['q' => 'save', 'status' => 'active']))
            ->assertOk()->assertSee('SAVE10')->assertSee('Summer');
        $this->put(route('admin.vouchers.update', $voucher), $this->payload('save20', 'Updated', 'percentage', 20))
            ->assertRedirect();
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'code' => 'SAVE20', 'name' => 'Updated',
            'discount_type' => 'percentage', 'discount_value' => 20, 'used_count' => 0]);
        $this->delete(route('admin.vouchers.destroy', $voucher))->assertRedirect();
        $this->assertSoftDeleted('vouchers', ['id' => $voucher->id]);
    }

    public function test_validation_enforces_unique_code_time_amounts_percentage_and_server_owned_count(): void
    {
        $admin = $this->user('admin');
        Voucher::query()->forceCreate($this->payload('UNIQUE', 'Existing') + ['used_count' => 0]);
        $invalid = array_merge($this->payload('unique', 'Invalid', 'percentage', 101), [
            'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_at' => now()->format('Y-m-d H:i:s'), 'min_order_amount' => -1,
            'usage_limit' => -1, 'used_count' => 999,
        ]);
        $this->actingAs($admin)->post(route('admin.vouchers.store'), $invalid)
            ->assertSessionHasErrors(['code', 'discount_value', 'end_at', 'min_order_amount', 'usage_limit', 'used_count']);
        $this->assertDatabaseCount('vouchers', 1);
    }

    public function test_historical_voucher_cannot_be_edited_or_deleted(): void
    {
        $manager = $this->user('manager');
        $voucher = Voucher::query()->forceCreate($this->payload('HISTORY', 'History') + ['used_count' => 1]);
        $opener = Employee::query()->forceCreate(['employee_code' => 'OPEN-VOUCHER', 'name' => 'Opener', 'status' => EmployeeStatus::Active]);
        $table = RestaurantTable::query()->forceCreate(['code' => 'VOUCHER-TABLE', 'name' => 'Table', 'capacity' => 2,
            'runtime_status' => RestaurantTableStatus::Occupied, 'is_active' => true]);
        $session = DiningSession::query()->forceCreate(['session_code' => 'DS-VOUCHER', 'table_id' => $table->id,
            'opened_by_employee_id' => $opener->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 2]);
        Bill::query()->forceCreate(['bill_code' => 'BIL-HISTORY', 'dining_session_id' => $session->id,
            'voucher_id' => $voucher->id, 'subtotal' => 10000, 'discount_amount' => 1000,
            'total_amount' => 9000, 'status' => BillStatus::Paid, 'issued_at' => now()]);
        $this->actingAs($manager)->put(route('admin.vouchers.update', $voucher), $this->payload('CHANGED', 'Changed'))
            ->assertSessionHasErrors('voucher');
        $this->delete(route('admin.vouchers.destroy', $voucher))->assertSessionHasErrors('voucher');
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'code' => 'HISTORY', 'deleted_at' => null]);
    }

    public function test_staff_and_actor_without_voucher_manage_are_forbidden(): void
    {
        $this->actingAs($this->user('staff'))->get(route('admin.vouchers.index'))->assertForbidden();
        $manager = $this->user('manager');
        $manager->role->permissions()->detach(Permission::where('code', 'voucher.manage')->firstOrFail());
        $this->actingAs($manager)->get(route('admin.vouchers.index'))->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function payload(string $code, string $name, string $type = 'fixed', int $value = 1000): array
    {
        return ['code' => $code, 'name' => $name, 'discount_type' => $type, 'discount_value' => $value,
            'min_order_amount' => 0, 'max_discount_amount' => null, 'start_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'end_at' => now()->addDay()->format('Y-m-d H:i:s'), 'usage_limit' => null, 'status' => Voucher::STATUS_ACTIVE];
    }

    private function user(string $role): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'V-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Voucher actor', 'status' => EmployeeStatus::Active]);

        return $user;
    }
}
