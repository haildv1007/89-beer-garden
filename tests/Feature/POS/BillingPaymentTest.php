<?php

namespace Tests\Feature\POS;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
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
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class BillingPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_open_billing_aggregates_all_orders_and_non_cancelled_historical_items(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product('Historical Beer', 10000);
        $this->item($session, $product, OrderItemStatus::Waiting, 2);
        $this->item($session, $product, OrderItemStatus::Preparing, 3);
        $this->item($session, $product, OrderItemStatus::Ready, 4);
        $this->item($session, $product, OrderItemStatus::Served, 5);
        $this->item($session, $product, OrderItemStatus::Cancelled, 50);
        $product->forceFill(['name' => 'Changed', 'price' => 999999])->save();
        $product->delete();

        $this->actingAs($staff)->post(route('pos.billing.open', $session))->assertRedirect();
        $bill = Bill::query()->sole();
        $this->assertSame(140000, $bill->subtotal);
        $this->assertSame(140000, $bill->total_amount);
        $this->assertSame(BillStatus::Unpaid, $bill->status);
        $this->get(route('pos.bills.show', $bill))->assertOk()->assertSee('Historical Beer')->assertDontSee('500.000');
    }

    public function test_double_open_refreshes_one_bill_and_empty_session_is_rejected(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product();
        $this->item($session, $product);
        $this->actingAs($staff)->post(route('pos.billing.open', $session))->assertRedirect();
        $this->item($session, $product, OrderItemStatus::Waiting, 2);
        $this->post(route('pos.billing.open', $session))->assertRedirect();
        $this->assertDatabaseCount('bills', 1);
        $this->assertSame(30000, Bill::query()->sole()->subtotal);

        $empty = $this->diningSession();
        $this->post(route('pos.billing.open', $empty))->assertSessionHasErrors('bill');
        $this->assertDatabaseCount('bills', 1);
    }

    public function test_fixed_percentage_caps_and_zero_total_policy_are_enforced(): void
    {
        $staff = $this->user('staff');
        $bill = $this->openedBill($staff, 100000);
        $fixed = $this->voucher('FIXED', Voucher::TYPE_FIXED, 15000);
        $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => ' fixed '])->assertRedirect();
        $this->assertSame([15000, 85000], [$bill->fresh()->discount_amount, $bill->fresh()->total_amount]);
        $this->assertSame(0, $fixed->fresh()->used_count);

        $percent = $this->voucher('PERCENT', Voucher::TYPE_PERCENTAGE, 25, max: 20000);
        $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => 'percent'])->assertRedirect();
        $this->assertSame([20000, 80000], [$bill->fresh()->discount_amount, $bill->fresh()->total_amount]);
        $this->assertSame($percent->id, $bill->fresh()->voucher_id);
        $this->delete(route('pos.bills.voucher.remove', $bill))->assertRedirect();
        $this->assertNull($bill->fresh()->voucher_id);

        $free = $this->voucher('FREE', Voucher::TYPE_PERCENTAGE, 100);
        $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => $free->code])->assertSessionHasErrors('voucher_code');
        $this->assertNull($bill->fresh()->voucher_id);
    }

    public function test_voucher_status_time_minimum_and_usage_are_revalidated(): void
    {
        $staff = $this->user('staff');
        $bill = $this->openedBill($staff, 50000);
        $invalid = [
            $this->voucher('INACTIVE', Voucher::TYPE_FIXED, 1000, status: Voucher::STATUS_INACTIVE),
            $this->voucher('FUTURE', Voucher::TYPE_FIXED, 1000, start: now()->addDay(), end: now()->addDays(2)),
            $this->voucher('EXPIRED', Voucher::TYPE_FIXED, 1000, start: now()->subDays(2), end: now()->subDay()),
            $this->voucher('MINIMUM', Voucher::TYPE_FIXED, 1000, minimum: 50001),
            $this->voucher('USED', Voucher::TYPE_FIXED, 1000, limit: 1, used: 1),
        ];
        foreach ($invalid as $voucher) {
            $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => $voucher->code])
                ->assertSessionHasErrors('voucher_code');
        }
        $this->assertNull($bill->fresh()->voucher_id);
    }

    public function test_failed_attempt_keeps_session_open_and_retry_success_completes_walk_in(): void
    {
        $staff = $this->user('staff');
        $bill = $this->openedBill($staff, 75000);
        $payload = ['method' => Payment::METHOD_BANK_TRANSFER, 'transaction_reference' => 'BANK-1'];
        $this->post(route('pos.bills.payments.complete', $bill), $payload)->assertSessionHasErrors('confirmed_received');
        $this->post(route('pos.bills.payments.fail', $bill), $payload + ['failure_reason' => 'Not received'])->assertRedirect();
        $failed = Payment::query()->sole();
        $this->assertSame(PaymentStatus::Failed, $failed->status);
        $this->assertNotNull($failed->failed_at);
        $this->assertSame(75000, $failed->amount);
        $this->assertSame(DiningSessionStatus::Active, $bill->diningSession->fresh()->status);

        $this->post(route('pos.bills.payments.complete', $bill), $payload + ['confirmed_received' => 'yes'])->assertRedirect(route('pos.bills.invoice', $bill));
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(1, Payment::query()->where('status', PaymentStatus::Success->value)->count());
        $this->assertSame(BillStatus::Paid, $bill->fresh()->status);
        $this->assertNotNull($bill->fresh()->issued_at);
        $this->assertSame(DiningSessionStatus::Completed, $bill->diningSession->fresh()->status);
        $this->assertNotNull($bill->diningSession->fresh()->ended_at);
        $this->assertSame($staff->employee->id, $bill->diningSession->fresh()->completed_by_employee_id);
        $this->assertSame(RestaurantTableStatus::Cleaning, $bill->diningSession->table->fresh()->runtime_status);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_success_recalculates_amount_consumes_voucher_once_and_completes_reservation(): void
    {
        $staff = $this->user('staff');
        $customer = Customer::query()->forceCreate(['name' => 'Reserved Guest']);
        $table = $this->table();
        $reservation = Reservation::query()->forceCreate([
            'customer_id' => $customer->id, 'table_id' => $table->id, 'reservation_code' => 'RSV-PAY',
            'reservation_date' => today(), 'reservation_time' => '18:00', 'party_size' => 2,
            'status' => ReservationStatus::CheckedIn, 'checked_in_at' => now(),
        ]);
        $session = $this->diningSession($table, $reservation, $customer);
        $product = $this->product();
        $this->item($session, $product, OrderItemStatus::Served, 5);
        $this->actingAs($staff)->post(route('pos.billing.open', $session));
        $bill = Bill::query()->sole();
        $voucher = $this->voucher('SAVE10', Voucher::TYPE_PERCENTAGE, 10, limit: 1);
        $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => $voucher->code]);
        $this->item($session, $product, OrderItemStatus::Waiting, 5);

        $this->post(route('pos.bills.payments.complete', $bill), $this->successPayload())->assertRedirect();
        $this->assertSame(90000, Payment::query()->sole()->amount);
        $this->assertSame([100000, 10000, 90000], [$bill->fresh()->subtotal, $bill->fresh()->discount_amount, $bill->fresh()->total_amount]);
        $this->assertSame(1, $voucher->fresh()->used_count);
        $this->assertSame(ReservationStatus::Completed, $reservation->fresh()->status);
        $this->assertNotNull($reservation->fresh()->completed_at);
        $this->post(route('pos.bills.payments.complete', $bill), $this->successPayload())->assertSessionHasErrors('payment');
        $this->assertSame(1, Payment::query()->where('status', 'success')->count());
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_completion_failure_rolls_back_every_entity(): void
    {
        $staff = $this->user('staff');
        $customer = Customer::query()->forceCreate(['name' => 'Rollback Guest']);
        $table = $this->table();
        $reservation = Reservation::query()->forceCreate(['customer_id' => $customer->id, 'table_id' => $table->id,
            'reservation_code' => 'RSV-ROLLBACK', 'reservation_date' => today(), 'reservation_time' => '18:00',
            'party_size' => 2, 'status' => ReservationStatus::CheckedIn, 'checked_in_at' => now()]);
        $session = $this->diningSession($table, $reservation, $customer);
        $this->item($session, $this->product());
        $this->actingAs($staff)->post(route('pos.billing.open', $session));
        $bill = Bill::query()->sole();
        $voucher = $this->voucher('ROLLBACK', Voucher::TYPE_FIXED, 1000);
        $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => $voucher->code]);
        Event::listen('eloquent.updating: '.Reservation::class, fn () => throw new RuntimeException('completion failed'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('pos.bills.payments.complete', $bill), $this->successPayload());
            $this->fail('Expected completion failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('completion failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(BillStatus::Unpaid, $bill->fresh()->status);
        $this->assertSame(0, $voucher->fresh()->used_count);
        $this->assertSame(DiningSessionStatus::Active, $session->fresh()->status);
        $this->assertSame(RestaurantTableStatus::Occupied, $table->fresh()->runtime_status);
        $this->assertSame(ReservationStatus::CheckedIn, $reservation->fresh()->status);
    }

    public function test_forged_payment_fields_are_rejected_and_permissions_are_independent(): void
    {
        $staff = $this->user('staff');
        $bill = $this->openedBill($staff, 10000);
        $forged = $this->successPayload() + ['amount' => 1, 'status' => 'success', 'processed_by_employee_id' => 999,
            'paid_at' => now(), 'failed_at' => now(), 'failure_reason' => 'forged'];
        $this->post(route('pos.bills.payments.complete', $bill), $forged)
            ->assertSessionHasErrors(['amount', 'status', 'processed_by_employee_id', 'paid_at', 'failed_at', 'failure_reason']);
        $this->assertDatabaseCount('payments', 0);

        foreach (['billing.view', 'voucher.apply', 'payment.complete'] as $permission) {
            $freshStaff = $this->user('staff');
            $permissionModel = Permission::where('code', $permission)->firstOrFail();
            $freshStaff->role->permissions()->detach($permissionModel);
            $this->actingAs($freshStaff);
            if ($permission === 'billing.view') {
                $this->get(route('pos.bills.show', $bill))->assertForbidden();
            } elseif ($permission === 'voucher.apply') {
                $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => 'X'])->assertForbidden();
            } else {
                $this->post(route('pos.bills.payments.complete', $bill), $this->successPayload())->assertForbidden();
            }
            $freshStaff->role->permissions()->attach($permissionModel);
        }
    }

    public function test_kitchen_customer_and_disabled_actor_cannot_access_billing(): void
    {
        $staff = $this->user('staff');
        $bill = $this->openedBill($staff, 10000);
        $this->actingAs($this->user('kitchen'))->get(route('pos.bills.show', $bill))->assertForbidden();
        $this->actingAs($this->user('customer', false))->get(route('pos.bills.show', $bill))->assertForbidden();
        $disabled = $this->user('staff');
        $disabled->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->actingAs($disabled)->get(route('pos.bills.show', $bill))->assertForbidden();
    }

    public function test_invoice_is_paid_only_read_only_and_uses_historical_snapshots(): void
    {
        $staff = $this->user('staff');
        $bill = $this->openedBill($staff, 42000, 'Invoice Snapshot');
        $this->get(route('pos.bills.invoice', $bill))->assertNotFound();
        $this->post(route('pos.bills.payments.complete', $bill), $this->successPayload())->assertRedirect();
        $this->get(route('pos.bills.invoice', $bill))->assertOk()->assertSee('Invoice Snapshot')->assertSee('42.000')->assertSee($staff->employee->name);
        $this->assertFalse(
            Schema::hasTable('invoices')
        );
    }

    public function test_post_paid_order_and_item_operations_are_blocked(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product();
        $item = $this->item($session, $product, OrderItemStatus::Waiting);
        $this->actingAs($staff)->post(route('pos.billing.open', $session));
        $bill = Bill::query()->sole();
        $this->post(route('pos.bills.payments.complete', $bill), $this->successPayload());
        $this->post(route('pos.bills.voucher.apply', $bill), ['voucher_code' => 'ANY'])->assertSessionHasErrors('bill');
        $this->delete(route('pos.bills.voucher.remove', $bill))->assertSessionHasErrors('bill');
        $this->post(route('pos.orders.store', $session), ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertSessionHasErrors('dining_session');
        $this->patch(route('pos.order-items.update', $item), ['quantity' => 2])->assertSessionHasErrors('order_item');
        $this->patch(route('pos.order-items.cancel-waiting', $item), ['cancellation_reason' => 'late'])->assertSessionHasErrors('order_item');
        $this->post(route('pos.billing.open', $session))->assertSessionHasErrors('bill');
    }

    private function openedBill(User $staff, int $amount, string $name = 'Product'): Bill
    {
        $session = $this->diningSession();
        $this->item($session, $this->product($name, $amount));
        $this->actingAs($staff)->post(route('pos.billing.open', $session))->assertRedirect();

        return Bill::query()->latest('id')->firstOrFail();
    }

    private function diningSession(?RestaurantTable $table = null, ?Reservation $reservation = null, ?Customer $customer = null): DiningSession
    {
        $opener = Employee::query()->forceCreate(['employee_code' => 'OPEN-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Opener', 'status' => EmployeeStatus::Active]);
        $table ??= $this->table();

        return DiningSession::query()->forceCreate(['session_code' => 'DS-'.fake()->unique()->numberBetween(1, 999999),
            'table_id' => $table->id, 'customer_id' => $customer?->id, 'reservation_id' => $reservation?->id,
            'opened_by_employee_id' => $opener->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 2]);
    }

    private function table(): RestaurantTable
    {
        return RestaurantTable::query()->forceCreate(['code' => 'T-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Table',
            'capacity' => 4, 'runtime_status' => RestaurantTableStatus::Occupied, 'is_active' => true]);
    }

    private function product(string $name = 'Product', int $price = 10000): Product
    {
        $category = Category::query()->first() ?? Category::query()->forceCreate(['name' => 'Food', 'slug' => 'food', 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1]);

        return Product::query()->forceCreate(['category_id' => $category->id, 'name' => $name,
            'slug' => 'p-'.fake()->unique()->numberBetween(1, 999999), 'price' => $price, 'status' => Product::STATUS_ACTIVE, 'is_available' => true]);
    }

    private function item(DiningSession $session, Product $product, OrderItemStatus $status = OrderItemStatus::Waiting, int $quantity = 1): OrderItem
    {
        $order = Order::query()->forceCreate(['order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id, 'source' => 'staff', 'ordered_at' => now()]);

        return OrderItem::query()->forceCreate(['order_id' => $order->id, 'product_id' => $product->id,
            'product_name' => $product->name, 'quantity' => $quantity, 'unit_price' => $product->price,
            'line_total' => $product->price * $quantity, 'status' => $status]);
    }

    private function voucher(string $code, string $type, int $value, ?int $max = null, string $status = Voucher::STATUS_ACTIVE,
        ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null, int $minimum = 0, ?int $limit = null, int $used = 0): Voucher
    {
        return Voucher::query()->forceCreate(['code' => $code, 'name' => $code, 'discount_type' => $type,
            'discount_value' => $value, 'min_order_amount' => $minimum, 'max_discount_amount' => $max,
            'start_at' => $start ?? now()->subHour(), 'end_at' => $end ?? now()->addHour(),
            'usage_limit' => $limit, 'used_count' => $used, 'status' => $status]);
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        if ($employee) {
            Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999),
                'name' => ucfirst($role).' Employee', 'status' => EmployeeStatus::Active]);
        }

        return $user;
    }

    /** @return array{method: string, confirmed_received: string} */
    private function successPayload(): array
    {
        return ['method' => Payment::METHOD_CASH, 'confirmed_received' => 'yes'];
    }
}
