<?php

namespace Tests\Feature\Customer;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private RestaurantTable $table;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $staff = User::factory()->forRole(Role::where('code', 'admin')->firstOrFail())->create();
        $this->employee = Employee::forceCreate(['user_id' => $staff->id, 'employee_code' => 'HIST-ADMIN', 'name' => 'Admin', 'status' => EmployeeStatus::Active]);
        $this->table = RestaurantTable::forceCreate(['code' => 'H-1', 'name' => 'Bàn lịch sử', 'capacity' => 4, 'runtime_status' => 'available', 'is_active' => true]);
        $category = Category::forceCreate(['name' => 'Món', 'slug' => 'history', 'status' => 'active', 'sort_order' => 1]);
        $this->product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Tên hiện tại', 'slug' => 'history-product', 'price' => 999, 'status' => 'active', 'is_available' => true]);
    }

    public function test_owner_sees_grouped_snapshot_additional_orders_and_correct_spending(): void
    {
        [$user, $customer] = $this->customer('owner@history.test');
        $session = $this->historySession($customer, 'DS-OWN', true);
        $this->order($session, 'ORD-1', 'Tên lịch sử', 2, 100, OrderItemStatus::Served);
        $this->order($session, 'ORD-2', 'Món đã hủy', 3, 200, OrderItemStatus::Cancelled);
        $this->paidBill($session, 200);
        $this->product->forceFill(['name' => 'Tên đã đổi', 'price' => 5000])->save();
        $this->product->delete();

        $this->actingAs($user)->get(route('customer.orders.history'))->assertOk()->assertSee('DS-OWN')->assertSee('2')->assertSee('200 đ');
        $this->get(route('customer.orders.history.show', $session))->assertOk()->assertSee('ORD-1')->assertSee('ORD-2')
            ->assertSee('Tên lịch sử')->assertSee('Món đã hủy')->assertDontSee('Tên đã đổi');
    }

    public function test_guest_missing_profile_other_customer_internal_actor_disabled_and_revoked_are_blocked(): void
    {
        [$owner, $customer] = $this->customer('owner2@history.test');
        $session = $this->historySession($customer, 'DS-PRIVATE');
        $this->get(route('customer.orders.history'))->assertRedirect(route('login'));
        $noProfile = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();
        $this->actingAs($noProfile)->get(route('customer.orders.history'))->assertNotFound();
        [$other] = $this->customer('other@history.test');
        $this->actingAs($other)->get(route('customer.orders.history.show', $session))->assertNotFound();
        $internal = $this->employee->user;
        $this->actingAs($internal)->get(route('customer.orders.history'))->assertForbidden();
        $owner->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->actingAs($owner)->get(route('customer.orders.history'))->assertRedirect(route('login'));
        $owner->forceFill(['status' => User::STATUS_ACTIVE])->save();
        $owner->role->permissions()->detach(Permission::where('code', 'customer.order.view-own')->firstOrFail());
        $this->actingAs($owner)->get(route('customer.orders.history'))->assertForbidden();
    }

    public function test_failed_and_unpaid_bills_do_not_inflate_overview_or_duplicate_paid_bill(): void
    {
        [$user, $customer] = $this->customer('money@history.test');
        $paid = $this->historySession($customer, 'DS-PAID', true);
        $this->order($paid, 'ORD-PAID', 'Paid', 1, 500, OrderItemStatus::Served);
        $this->paidBill($paid, 500);
        $unpaid = $this->historySession($customer, 'DS-UNPAID', true);
        $this->order($unpaid, 'ORD-UNPAID', 'Unpaid', 1, 900, OrderItemStatus::Served);
        Bill::forceCreate(['bill_code' => 'B-UNPAID', 'dining_session_id' => $unpaid->id, 'subtotal' => 900, 'discount_amount' => 0, 'total_amount' => 900, 'status' => BillStatus::Unpaid]);
        $failed = $this->historySession($customer, 'DS-FAILED', true);
        $bill = Bill::forceCreate(['bill_code' => 'B-FAILED', 'dining_session_id' => $failed->id, 'subtotal' => 700, 'discount_amount' => 0, 'total_amount' => 700, 'status' => BillStatus::Paid]);
        Payment::forceCreate(['payment_code' => 'P-FAILED', 'bill_id' => $bill->id, 'processed_by_employee_id' => $this->employee->id, 'method' => 'cash', 'amount' => 700, 'status' => PaymentStatus::Failed]);

        $this->actingAs($user)->get(route('customer.orders.history'))->assertOk()->assertSee('500 đ')->assertDontSee('1.200 đ');
    }

    public function test_filters_validate_range_and_get_is_read_only(): void
    {
        [$user, $customer] = $this->customer('filter@history.test');
        $this->historySession($customer, 'DS-FILTER');
        $before = [DiningSession::count(), Order::count(), Bill::count(), Payment::count()];
        $this->actingAs($user)->get(route('customer.orders.history', ['q' => 'FILTER', 'status' => 'active', 'from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()]))->assertOk()->assertSee('DS-FILTER');
        $this->get(route('customer.orders.history', ['from' => '2026-08-25', 'to' => '2025-01-01']))->assertSessionHasErrors('to');
        $this->get(route('customer.orders.history', ['from' => '2024-01-01', 'to' => '2026-01-02']))->assertSessionHasErrors('to');
        $this->assertSame($before, [DiningSession::count(), Order::count(), Bill::count(), Payment::count()]);
    }

    public function test_admin_customer_detail_requires_customer_view_and_active_employee(): void
    {
        [, $customer] = $this->customer('admin-view@history.test');
        $this->historySession($customer, 'DS-ADMIN');
        $admin = $this->employee->user;
        $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertOk()->assertSee('DS-ADMIN');
        $admin->role->permissions()->detach(Permission::where('code', 'customer.view')->firstOrFail());
        $this->get(route('admin.customers.show', $customer))->assertForbidden();
        $admin->role->permissions()->attach(Permission::where('code', 'customer.view')->firstOrFail());
        $this->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->get(route('admin.customers.show', $customer))->assertForbidden();
    }

    private function customer(string $email): array
    {
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create(['email' => $email]);

        return [$user, Customer::forceCreate(['user_id' => $user->id, 'name' => $email])];
    }

    private function historySession(Customer $customer, string $code, bool $completed = false): DiningSession
    {
        return DiningSession::forceCreate(['session_code' => $code, 'table_id' => $this->table->id, 'customer_id' => $customer->id, 'opened_by_employee_id' => $this->employee->id, 'completed_by_employee_id' => $completed ? $this->employee->id : null, 'status' => $completed ? DiningSessionStatus::Completed : DiningSessionStatus::Active, 'started_at' => now(), 'ended_at' => $completed ? now() : null, 'guest_count' => 2]);
    }

    private function order(DiningSession $session, string $code, string $name, int $quantity, int $price, OrderItemStatus $status): void
    {
        $order = Order::forceCreate(['order_code' => $code, 'dining_session_id' => $session->id, 'created_by_customer_id' => $session->customer_id, 'source' => 'customer', 'ordered_at' => now()]);
        OrderItem::forceCreate(['order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => $name, 'quantity' => $quantity, 'unit_price' => $price, 'line_total' => $quantity * $price, 'status' => $status]);
    }

    private function paidBill(DiningSession $session, int $amount): void
    {
        $bill = Bill::forceCreate(['bill_code' => 'B-'.$session->session_code, 'dining_session_id' => $session->id, 'subtotal' => $amount, 'discount_amount' => 0, 'total_amount' => $amount, 'status' => BillStatus::Paid, 'issued_at' => now()]);
        Payment::forceCreate(['payment_code' => 'P-'.$session->session_code, 'bill_id' => $bill->id, 'processed_by_employee_id' => $this->employee->id, 'method' => 'cash', 'amount' => $amount, 'status' => PaymentStatus::Success, 'paid_at' => now()]);
    }
}
