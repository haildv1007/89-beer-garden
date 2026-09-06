<?php

namespace Tests\Feature\Customer;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CustomerOrder\CustomerDiningContextService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCheckoutVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        SystemSetting::forceCreate(['key' => 'customer_ordering_enabled', 'value' => 'true', 'type' => 'boolean']);
    }

    public function test_guest_and_authenticated_customer_with_bound_context_can_view_read_only_checkout(): void
    {
        [$session, $bill] = $this->checkout(10000);
        $state = [Bill::count(), $bill->updated_at->toJSON()];
        $this->bound($session)
            ->get(route('customer.checkout.show'))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee('Snapshot')
            ->assertDontSee('transaction_reference');
        $customer = User::factory()
            ->forRole(Role::where('code', 'customer')->firstOrFail())
            ->create();
        Customer::forceCreate(['user_id' => $customer->id, 'name' => 'Customer']);
        $this->actingAs($customer)->bound($session)->get(route('customer.checkout.show'))->assertOk();
        $this->assertSame($state, [Bill::count(), $bill->fresh()->updated_at->toJSON()]);
    }

    public function test_missing_context_and_internal_employee_are_blocked_and_no_bill_does_not_write(): void
    {
        $this->get(route('customer.checkout.show'))->assertSessionHasErrors('context');
        [$session] = $this->checkout(10000, false);
        $this->bound($session)->get(route('customer.checkout.show'))->assertOk()->assertSee(__('checkout.no_bill'));
        $this->assertDatabaseCount('bills', 0);
        $staff = User::factory()
            ->forRole(Role::where('code', 'staff')->firstOrFail())
            ->create();
        Employee::forceCreate([
            'user_id' => $staff->id,
            'employee_code' => 'CHK-STF',
            'name' => 'Staff',
            'status' => EmployeeStatus::Active,
        ]);
        $this->actingAs($staff)
            ->bound($session)
            ->get(route('customer.checkout.show'))
            ->assertSessionHasErrors('context');
    }

    public function test_fixed_percentage_cap_normalization_replacement_and_remove_use_shared_calculation(): void
    {
        [$session, $bill] = $this->checkout(100000);
        $fixed = $this->voucher('FIXED', Voucher::TYPE_FIXED, 15000);
        $percent = $this->voucher('PERCENT', Voucher::TYPE_PERCENTAGE, 25, 20000);
        $this->bound($session)
            ->post(route('customer.checkout.voucher.apply'), ['voucher_code' => ' fixed '])
            ->assertRedirect();
        $this->assertSame(
            [15000, 85000, 0],
            [$bill->fresh()->discount_amount, $bill->fresh()->total_amount, $fixed->fresh()->used_count],
        );
        $this->post(route('customer.checkout.voucher.apply'), ['voucher_code' => 'percent'])->assertRedirect();
        $this->assertSame(
            [$percent->id, 20000, 80000],
            [$bill->fresh()->voucher_id, $bill->fresh()->discount_amount, $bill->fresh()->total_amount],
        );
        $this->delete(route('customer.checkout.voucher.remove'))->assertRedirect();
        $this->assertSame(
            [null, 0, 100000, 0],
            [
                $bill->fresh()->voucher_id,
                $bill->fresh()->discount_amount,
                $bill->fresh()->total_amount,
                $percent->fresh()->used_count,
            ],
        );
    }

    public function test_invalid_minimum_time_usage_zero_total_and_forged_fields_fail_closed(): void
    {
        [$session, $bill] = $this->checkout(50000);
        foreach (
            [
                $this->voucher('MIN', Voucher::TYPE_FIXED, 1000, minimum: 50001),
                $this->voucher('USED', Voucher::TYPE_FIXED, 1000, limit: 1, used: 1),
                $this->voucher('FUTURE', Voucher::TYPE_FIXED, 1000, start: now()->addDay()),
                $this->voucher('FREE', Voucher::TYPE_PERCENTAGE, 100),
            ] as $voucher
        ) {
            $this->bound($session)
                ->post(route('customer.checkout.voucher.apply'), ['voucher_code' => $voucher->code])
                ->assertSessionHasErrors('voucher_code');
        }
        $this->post(route('customer.checkout.voucher.apply'), ['voucher_code' => 'NONE'])->assertSessionHasErrors(
            'voucher_code',
        );
        $this->post(route('customer.checkout.voucher.apply'), [
            'voucher_code' => 'MIN',
            'bill_id' => 999,
            'subtotal' => 1,
            'status' => 'paid',
        ])->assertSessionHasErrors(['bill_id', 'subtotal', 'status']);
        $this->assertNull($bill->fresh()->voucher_id);
    }

    public function test_recalculation_uses_additional_snapshot_items_and_excludes_cancelled(): void
    {
        [$session, $bill, $product] = $this->checkout(10000);
        $this->item($session, $product, 20000, OrderItemStatus::Waiting, 'Additional');
        $this->item($session, $product, 999999, OrderItemStatus::Cancelled, 'Cancelled');
        $product->forceFill(['price' => 777777])->save();
        $voucher = $this->voucher('TEN', Voucher::TYPE_PERCENTAGE, 10);
        $this->bound($session)->post(route('customer.checkout.voucher.apply'), ['voucher_code' => $voucher->code]);
        $this->assertSame(
            [30000, 3000, 27000],
            [$bill->fresh()->subtotal, $bill->fresh()->discount_amount, $bill->fresh()->total_amount],
        );
    }

    public function test_double_submit_and_competing_codes_leave_one_complete_voucher_state(): void
    {
        [$session, $bill] = $this->checkout(100000);
        $first = $this->voucher('FIRST', Voucher::TYPE_FIXED, 10000);
        $second = $this->voucher('SECOND', Voucher::TYPE_FIXED, 25000);

        $this->bound($session)
            ->post(route('customer.checkout.voucher.apply'), ['voucher_code' => $first->code])
            ->assertRedirect();
        $this->post(route('customer.checkout.voucher.apply'), ['voucher_code' => $first->code])->assertRedirect();
        $this->post(route('customer.checkout.voucher.apply'), ['voucher_code' => $second->code])->assertRedirect();

        $this->assertSame(
            [$second->id, 100000, 25000, 75000],
            [
                $bill->fresh()->voucher_id,
                $bill->fresh()->subtotal,
                $bill->fresh()->discount_amount,
                $bill->fresh()->total_amount,
            ],
        );
        $this->assertSame([0, 0], [$first->fresh()->used_count, $second->fresh()->used_count]);
    }

    public function test_paid_inactive_wrong_context_and_disabled_or_malformed_capability_are_blocked(): void
    {
        [$first, $bill] = $this->checkout(10000);
        [$second] = $this->checkout(20000);
        $voucher = $this->voucher('SAFE', Voucher::TYPE_FIXED, 1000);
        $this->bound($second)
            ->post(route('customer.checkout.voucher.apply'), ['voucher_code' => $voucher->code, 'bill_id' => $bill->id])
            ->assertSessionHasErrors('bill_id');
        $bill->forceFill(['status' => BillStatus::Paid])->save();
        $this->bound($first)
            ->post(route('customer.checkout.voucher.apply'), ['voucher_code' => 'SAFE'])
            ->assertSessionHasErrors();
        $bill->forceFill(['status' => BillStatus::Unpaid])->save();
        SystemSetting::where('key', 'customer_ordering_enabled')->update(['value' => 'false']);
        $this->bound($first)->get(route('customer.checkout.show'))->assertSessionHasErrors('context');
        SystemSetting::where('key', 'customer_ordering_enabled')->update(['value' => 'malformed']);
        $this->bound($first)->get(route('customer.checkout.show'))->assertSessionHasErrors('context');
    }

    private function bound(DiningSession $session): static
    {
        return $this->withSession([CustomerDiningContextService::SESSION_KEY => ['dining_session_id' => $session->id]]);
    }

    private function checkout(int $amount, bool $withBill = true): array
    {
        $employee = Employee::forceCreate([
            'employee_code' => 'OP-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Opener',
            'status' => EmployeeStatus::Active,
        ]);
        $table = RestaurantTable::forceCreate([
            'code' => 'CT-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Table',
            'capacity' => 4,
            'runtime_status' => RestaurantTableStatus::Occupied,
            'is_active' => true,
        ]);
        $session = DiningSession::forceCreate([
            'session_code' => 'CS-'.fake()->unique()->numberBetween(1, 999999),
            'table_id' => $table->id,
            'opened_by_employee_id' => $employee->id,
            'status' => DiningSessionStatus::Active,
            'started_at' => now(),
            'guest_count' => 2,
        ]);
        $category =
            Category::first() ??
            Category::forceCreate([
                'name' => 'Food',
                'slug' => 'checkout-food',
                'status' => 'active',
                'sort_order' => 1,
            ]);
        $product = Product::forceCreate([
            'category_id' => $category->id,
            'name' => 'Current',
            'slug' => 'cp-'.fake()->unique()->numberBetween(1, 999999),
            'price' => $amount,
            'status' => 'active',
            'is_available' => true,
        ]);
        $this->item($session, $product, $amount, OrderItemStatus::Waiting, 'Snapshot');
        $bill = $withBill
            ? Bill::forceCreate([
                'bill_code' => 'CB-'.fake()->unique()->numberBetween(1, 999999),
                'dining_session_id' => $session->id,
                'subtotal' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'status' => BillStatus::Unpaid,
            ])
            : null;

        return [$session, $bill, $product];
    }

    private function item(
        DiningSession $session,
        Product $product,
        int $amount,
        OrderItemStatus $status,
        string $name,
    ): void {
        $order = Order::forceCreate([
            'order_code' => 'CO-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id,
            'source' => 'customer',
            'ordered_at' => now(),
        ]);
        OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $name,
            'quantity' => 1,
            'unit_price' => $amount,
            'line_total' => $amount,
            'status' => $status,
        ]);
    }

    private function voucher(
        string $code,
        string $type,
        int $value,
        ?int $max = null,
        int $minimum = 0,
        ?int $limit = null,
        int $used = 0,
        $start = null,
    ): Voucher {
        return Voucher::forceCreate([
            'code' => $code,
            'name' => $code,
            'discount_type' => $type,
            'discount_value' => $value,
            'min_order_amount' => $minimum,
            'max_discount_amount' => $max,
            'start_at' => $start ?? now()->subHour(),
            'end_at' => now()->addDays(2),
            'usage_limit' => $limit,
            'used_count' => $used,
            'status' => 'active',
        ]);
    }
}
