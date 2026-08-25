<?php

namespace Tests\Feature\Admin;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Bill;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use App\Queries\Reports\OperationalReportQuery;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-25 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_manager_and_admin_can_view_but_other_roles_cannot(): void
    {
        $this->actingAs($this->user('manager'))->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($this->user('admin'))->get(route('admin.reports.index'))->assertOk();
        foreach (['staff', 'kitchen'] as $role) {
            $this->actingAs($this->user($role))->get(route('admin.reports.index'))->assertForbidden();
        }
        $this->actingAs(User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create())
            ->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_disabled_user_or_employee_cannot_view_reports(): void
    {
        $disabledUser = $this->user('manager');
        $disabledUser->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->actingAs($disabledUser)->get(route('admin.reports.index'))->assertRedirect(route('login'));

        $disabledEmployee = $this->user('manager');
        $disabledEmployee->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->actingAs($disabledEmployee)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_revenue_valid_orders_aov_and_cancelled_items_are_calculated_from_paid_successful_data(): void
    {
        $employee = $this->employee();
        $first = $this->sale($employee, 120000, 'Beer', 2, OrderItemStatus::Served, '2026-08-25 00:00:00');
        $this->item($first['order'], $first['product'], 1, OrderItemStatus::Cancelled, 'Cancelled snapshot');
        $second = $this->sale($employee, 80000, 'Food', 1, OrderItemStatus::Ready, '2026-08-25 23:59:59');
        $failed = $this->sale($employee, 999000, 'Failed', 1, OrderItemStatus::Served, '2026-08-25 10:00:00', PaymentStatus::Failed);
        $failed['bill']->forceFill(['status' => BillStatus::Unpaid])->save();
        $cancelledOnly = $this->sale($employee, 50000, 'Void', 1, OrderItemStatus::Cancelled, '2026-08-25 10:00:00');

        $report = $this->query('2026-08-25 00:00:00', '2026-08-25 23:59:59');
        $this->assertSame(250000, $report['revenue']);
        $this->assertSame(2, $report['validOrderCount']);
        $this->assertSame(125000, $report['averageOrderValue']);
        $this->assertSame(['Beer', 'Food'], $report['topProducts']->pluck('product_name')->all());
        $this->assertCount(3, $report['payments']);
        $this->assertSame($second['payment']->payment_code, $report['payments']->first()->payment_code);
        $this->assertNotContains('Cancelled snapshot', $report['topProducts']->pluck('product_name'));
        $this->assertSame(50000, $cancelledOnly['payment']->amount);
    }

    public function test_successful_payment_is_counted_once_per_bill_and_payment_amount_is_authoritative(): void
    {
        $employee = $this->employee();
        $sale = $this->sale($employee, 70000, 'Snapshot', 1, OrderItemStatus::Served, '2026-08-25 10:00:00');
        $sale['bill']->forceFill(['total_amount' => 60000])->save();
        Payment::query()->forceCreate(['payment_code' => 'PAY-DUPLICATE', 'bill_id' => $sale['bill']->id,
            'processed_by_employee_id' => $employee->id, 'method' => Payment::METHOD_CASH, 'amount' => 999999,
            'status' => PaymentStatus::Success, 'paid_at' => '2026-08-25 11:00:00']);

        $report = $this->query('2026-08-25 00:00:00', '2026-08-25 23:59:59');
        $this->assertSame(70000, $report['revenue']);
        $this->assertCount(1, $report['payments']);
        $this->assertSame(1, $report['validOrderCount']);
    }

    public function test_top_products_use_snapshots_aggregate_by_product_and_have_deterministic_ranking(): void
    {
        $employee = $this->employee();
        $saleA = $this->sale($employee, 10000, 'Alpha old', 2, OrderItemStatus::Served, '2026-08-25 09:00:00');
        $this->item($saleA['order'], $saleA['product'], 1, OrderItemStatus::Ready, 'Alpha new');
        $this->sale($employee, 15000, 'Beta', 3, OrderItemStatus::Served, '2026-08-25 10:00:00');

        $top = $this->query('2026-08-25 00:00:00', '2026-08-25 23:59:59')['topProducts'];
        $this->assertCount(2, $top);
        $this->assertSame(3, (int) $top[0]->quantity);
        $this->assertSame('Alpha new', $top[0]->product_name);
        $this->assertSame(15000, (int) $top[0]->line_revenue);
        $this->assertSame('Beta', $top[1]->product_name);
    }

    public function test_product_changes_and_soft_delete_do_not_change_historical_snapshots(): void
    {
        $sale = $this->sale($this->employee(), 42000, 'Historical Beer', 2, OrderItemStatus::Served, '2026-08-25 09:00:00');
        $sale['product']->forceFill(['name' => 'Renamed Beer', 'price' => 999999])->save();
        $sale['product']->delete();

        $top = $this->query('2026-08-25 00:00:00', '2026-08-25 23:59:59')['topProducts']->sole();
        $this->assertSame('Historical Beer', $top->product_name);
        $this->assertSame(42000, (int) $top->line_revenue);
        $this->assertSame(2, (int) $top->quantity);
    }

    public function test_additional_orders_in_one_paid_session_are_counted_separately_without_revenue_fanout(): void
    {
        $sale = $this->sale($this->employee(), 60000, 'Round one', 1, OrderItemStatus::Served, '2026-08-25 09:00:00');
        $additional = Order::query()->forceCreate(['order_code' => 'ORD-ADDITIONAL',
            'dining_session_id' => $sale['order']->dining_session_id, 'source' => 'customer', 'ordered_at' => now()]);
        $this->item($additional, $sale['product'], 2, OrderItemStatus::Ready, 'Round two snapshot');

        $report = $this->query('2026-08-25 00:00:00', '2026-08-25 23:59:59');
        $this->assertSame(60000, $report['revenue']);
        $this->assertSame(2, $report['validOrderCount']);
        $this->assertSame(30000, $report['averageOrderValue']);
        $this->assertSame(3, (int) $report['topProducts']->sole()->quantity);
    }

    public function test_empty_range_returns_zero_values_and_date_filters_are_inclusive(): void
    {
        $employee = $this->employee();
        $this->sale($employee, 1, 'Start', 1, OrderItemStatus::Served, '2026-08-24 23:59:59');
        $this->sale($employee, 2, 'Inside', 1, OrderItemStatus::Served, '2026-08-25 00:00:00');
        $inside = $this->query('2026-08-25 00:00:00', '2026-08-25 23:59:59');
        $empty = $this->query('2026-08-20 00:00:00', '2026-08-20 23:59:59');
        $this->assertSame(2, $inside['revenue']);
        $this->assertSame([0, 0, 0], [$empty['revenue'], $empty['validOrderCount'], $empty['averageOrderValue']]);
    }

    public function test_date_validation_default_presets_and_input_whitelist_are_enforced(): void
    {
        $manager = $this->user('manager');
        $this->actingAs($manager)->get(route('admin.reports.index'))->assertOk()->assertSee('2026-07-27');
        $this->get(route('admin.reports.index', ['preset' => 'custom', 'from' => '2026-08-26', 'to' => '2026-08-25']))->assertSessionHasErrors('to');
        $this->get(route('admin.reports.index', ['preset' => 'custom', 'from' => '2025-01-01', 'to' => '2026-08-25']))->assertSessionHasErrors('to');
        $this->get(route('admin.reports.index', ['preset' => "30' OR 1=1 --"]))->assertSessionHasErrors('preset');
        $this->get(route('admin.reports.index', ['sort' => 'amount desc']))->assertSessionHasErrors('sort');
    }

    public function test_reservations_use_created_at_and_get_request_does_not_write(): void
    {
        $manager = $this->user('manager');
        $customer = DB::table('customers')->insertGetId(['name' => 'Guest', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('reservations')->insert(['customer_id' => $customer, 'reservation_code' => 'RSV-REPORT',
            'reservation_date' => '2026-09-01', 'reservation_time' => '18:00:00', 'party_size' => 2,
            'status' => 'pending', 'created_at' => '2026-08-25 08:00:00', 'updated_at' => now()]);
        $before = ['bills' => DB::table('bills')->count(), 'payments' => DB::table('payments')->count(),
            'orders' => DB::table('orders')->count(), 'reservations' => DB::table('reservations')->count()];
        $this->actingAs($manager)->get(route('admin.reports.index', ['preset' => 'today']))
            ->assertOk()->assertSee(__('report.reservation_basis'))->assertSee(__('reservation.statuses.pending'));
        $this->assertSame($before, ['bills' => DB::table('bills')->count(), 'payments' => DB::table('payments')->count(),
            'orders' => DB::table('orders')->count(), 'reservations' => DB::table('reservations')->count()]);
    }

    /** @return array<string, mixed> */
    private function query(string $from, string $to): array
    {
        return app(OperationalReportQuery::class)->run(CarbonImmutable::parse($from), CarbonImmutable::parse($to));
    }

    /** @return array{bill: Bill, payment: Payment, order: Order, product: Product} */
    private function sale(Employee $employee, int $amount, string $name, int $quantity, OrderItemStatus $status,
        string $paidAt, PaymentStatus $paymentStatus = PaymentStatus::Success): array
    {
        $table = RestaurantTable::query()->forceCreate(['code' => 'T-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Table', 'capacity' => 4, 'runtime_status' => RestaurantTableStatus::Occupied, 'is_active' => true]);
        $session = DiningSession::query()->forceCreate(['session_code' => 'DS-'.fake()->unique()->numberBetween(1, 999999),
            'table_id' => $table->id, 'opened_by_employee_id' => $employee->id, 'status' => DiningSessionStatus::Completed,
            'started_at' => now()->subHour(), 'ended_at' => now(), 'guest_count' => 2]);
        $category = Category::query()->firstOrCreate(['slug' => 'report'], ['name' => 'Report', 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1]);
        $product = Product::query()->forceCreate(['category_id' => $category->id, 'name' => $name,
            'slug' => 'p-'.fake()->unique()->numberBetween(1, 999999), 'price' => intdiv($amount, $quantity),
            'status' => Product::STATUS_ACTIVE, 'is_available' => true]);
        $order = Order::query()->forceCreate(['order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id, 'created_by_employee_id' => $employee->id,
            'source' => 'staff', 'ordered_at' => now()]);
        $this->item($order, $product, $quantity, $status, $name);
        $bill = Bill::query()->forceCreate(['bill_code' => 'BILL-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id, 'subtotal' => $amount, 'discount_amount' => 0,
            'total_amount' => $amount, 'status' => $paymentStatus === PaymentStatus::Success ? BillStatus::Paid : BillStatus::Unpaid,
            'issued_at' => $paidAt]);
        $payment = Payment::query()->forceCreate(['payment_code' => 'PAY-'.fake()->unique()->numberBetween(1, 999999),
            'bill_id' => $bill->id, 'processed_by_employee_id' => $employee->id, 'method' => Payment::METHOD_CASH,
            'amount' => $amount, 'status' => $paymentStatus, 'paid_at' => $paidAt]);

        return compact('bill', 'payment', 'order', 'product');
    }

    private function item(Order $order, Product $product, int $quantity, OrderItemStatus $status, string $snapshot): OrderItem
    {
        return OrderItem::query()->forceCreate(['order_id' => $order->id, 'product_id' => $product->id,
            'product_name' => $snapshot, 'quantity' => $quantity, 'unit_price' => $product->price,
            'line_total' => $product->price * $quantity, 'status' => $status]);
    }

    private function employee(): Employee
    {
        return Employee::query()->forceCreate(['employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Cashier', 'status' => EmployeeStatus::Active]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999),
            'name' => ucfirst($role), 'status' => EmployeeStatus::Active]);

        return $user;
    }
}
