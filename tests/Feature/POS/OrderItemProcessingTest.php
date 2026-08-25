<?php

namespace Tests\Feature\POS;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class OrderItemProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_marks_only_ready_item_served_without_completing_any_aggregate(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $item = $this->item($session, OrderItemStatus::Ready);
        $snapshot = [$item->product_name, $item->quantity, $item->unit_price, $item->line_total];
        $this->actingAs($staff)->patch(route('pos.order-items.mark-served', $item))->assertRedirect();
        $item->refresh();
        $this->assertSame(OrderItemStatus::Served, $item->status);
        $this->assertSame($snapshot, [$item->product_name, $item->quantity, $item->unit_price, $item->line_total]);
        $this->assertSame(DiningSessionStatus::Active, $session->fresh()->status);
        $this->assertSame(RestaurantTableStatus::Occupied, $session->table->fresh()->runtime_status);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->patch(route('pos.order-items.mark-served', $item))->assertSessionHasErrors('order_item');
    }

    public function test_kitchen_cannot_mark_served_and_missing_permission_is_forbidden(): void
    {
        $item = $this->item($this->diningSession(), OrderItemStatus::Ready);
        $this->actingAs($this->user('kitchen'))->patch(route('pos.order-items.mark-served', $item))->assertForbidden();
        $waiting = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->patch(route('pos.order-items.cancel-waiting', $waiting), ['cancellation_reason' => 'Forged'])->assertForbidden();
        $staff = $this->user('staff');
        $staff->role->permissions()->detach(Permission::where('code', 'order-item.mark-served')->firstOrFail());
        $this->actingAs($staff)->patch(route('pos.order-items.mark-served', $item))->assertForbidden();
        $this->actingAs($this->user('customer', false))->patch(route('pos.order-items.mark-served', $item))->assertForbidden();
    }

    public function test_waiting_cancellation_records_server_owned_audit_and_trimmed_reason(): void
    {
        $staff = $this->user('staff');
        $item = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->actingAs($staff)->patch(route('pos.order-items.cancel-waiting', $item), ['cancellation_reason' => '  Customer changed mind  '])->assertRedirect();
        $item->refresh();
        $this->assertSame(OrderItemStatus::Cancelled, $item->status);
        $this->assertSame($staff->employee->id, $item->cancelled_by_employee_id);
        $this->assertNotNull($item->cancelled_at);
        $this->assertSame('Customer changed mind', $item->cancellation_reason);
    }

    public function test_preparing_cancellation_is_manager_admin_only_by_permission_matrix(): void
    {
        foreach (['manager', 'admin'] as $role) {
            $actor = $this->user($role);
            $item = $this->item($this->diningSession(), OrderItemStatus::Preparing);
            $this->actingAs($actor)->patch(route('pos.order-items.cancel-preparing', $item), ['cancellation_reason' => 'Manager decision'])->assertRedirect();
            $this->assertSame(OrderItemStatus::Cancelled, $item->fresh()->status);
        }

        foreach (['staff', 'kitchen'] as $role) {
            $item = $this->item($this->diningSession(), OrderItemStatus::Preparing);
            $this->actingAs($this->user($role))->patch(route('pos.order-items.cancel-preparing', $item), ['cancellation_reason' => 'Forged'])->assertForbidden();
            $this->assertSame(OrderItemStatus::Preparing, $item->fresh()->status);
        }
    }

    public function test_ready_served_cancelled_and_wrong_cancel_endpoint_are_rejected(): void
    {
        $manager = $this->user('manager');
        foreach ([OrderItemStatus::Ready, OrderItemStatus::Served, OrderItemStatus::Cancelled] as $status) {
            $item = $this->item($this->diningSession(), $status);
            $this->actingAs($manager)->patch(route('pos.order-items.cancel-preparing', $item), ['cancellation_reason' => 'Invalid'])->assertSessionHasErrors('order_item');
        }
        $preparing = $this->item($this->diningSession(), OrderItemStatus::Preparing);
        $this->patch(route('pos.order-items.cancel-waiting', $preparing), ['cancellation_reason' => 'Wrong endpoint'])->assertSessionHasErrors('order_item');
        $waiting = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->patch(route('pos.order-items.cancel-preparing', $waiting), ['cancellation_reason' => 'Wrong endpoint'])->assertSessionHasErrors('order_item');
        $this->patch(route('pos.order-items.mark-served', $waiting))->assertSessionHasErrors('order_item');
        $preparingForServed = $this->item($this->diningSession(), OrderItemStatus::Preparing);
        $this->patch(route('pos.order-items.mark-served', $preparingForServed))->assertSessionHasErrors('order_item');
    }

    public function test_cancellation_requires_reason_and_rejects_forged_audit_fields(): void
    {
        $staff = $this->user('staff');
        $item = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->actingAs($staff)->patch(route('pos.order-items.cancel-waiting', $item), ['cancellation_reason' => '   '])->assertSessionHasErrors('cancellation_reason');
        $this->patch(route('pos.order-items.cancel-waiting', $item), [
            'cancellation_reason' => 'Valid', 'status' => 'served',
            'cancelled_by_employee_id' => 999, 'cancelled_at' => now(),
            'quantity' => 99, 'unit_price' => 1, 'line_total' => 1,
        ])->assertSessionHasErrors(['status', 'cancelled_by_employee_id', 'cancelled_at', 'quantity', 'unit_price', 'line_total']);
        $this->assertSame(OrderItemStatus::Waiting, $item->fresh()->status);
        $this->assertNull($item->fresh()->cancelled_by_employee_id);
    }

    public function test_completed_session_blocks_served_and_cancellation(): void
    {
        $manager = $this->user('manager');
        $session = $this->diningSession(DiningSessionStatus::Completed);
        $ready = $this->item($session, OrderItemStatus::Ready);
        $waiting = $this->item($session, OrderItemStatus::Waiting);
        $this->actingAs($manager)->patch(route('pos.order-items.mark-served', $ready))->assertSessionHasErrors('order_item');
        $this->patch(route('pos.order-items.cancel-waiting', $waiting), ['cancellation_reason' => 'No'])->assertSessionHasErrors('order_item');
    }

    public function test_each_pos_permission_and_disabled_actor_are_enforced(): void
    {
        $manager = $this->user('manager');
        $cases = [
            ['order-item.mark-served', 'pos.order-items.mark-served', OrderItemStatus::Ready, []],
            ['order-item.cancel-waiting', 'pos.order-items.cancel-waiting', OrderItemStatus::Waiting, ['cancellation_reason' => 'Reason']],
            ['order-item.cancel-preparing', 'pos.order-items.cancel-preparing', OrderItemStatus::Preparing, ['cancellation_reason' => 'Reason']],
        ];
        foreach ($cases as [$permission, $route, $status, $payload]) {
            $item = $this->item($this->diningSession(), $status);
            $model = Permission::where('code', $permission)->firstOrFail();
            $manager->role->permissions()->detach($model);
            $this->actingAs($manager)->patch(route($route, $item), $payload)->assertForbidden();
            $manager->role->permissions()->attach($model);
        }
        $manager->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $item = $this->item($this->diningSession(), OrderItemStatus::Ready);
        $this->actingAs($manager)->patch(route('pos.order-items.mark-served', $item))->assertForbidden();
        $manager->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->patch(route('pos.order-items.mark-served', $item))->assertRedirect(route('login'));
    }

    public function test_cancellation_save_failure_rolls_back_status_and_every_audit_field(): void
    {
        $staff = $this->user('staff');
        $item = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        Event::listen('eloquent.saving: '.OrderItem::class, function (OrderItem $saving): void {
            if ($saving->isDirty('status')) {
                throw new RuntimeException('cancel failed');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($staff)->patch(route('pos.order-items.cancel-waiting', $item), ['cancellation_reason' => 'Reason']);
            $this->fail('Expected cancellation failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('cancel failed', $exception->getMessage());
        }
        $item->refresh();
        $this->assertSame(OrderItemStatus::Waiting, $item->status);
        $this->assertNull($item->cancelled_by_employee_id);
        $this->assertNull($item->cancelled_at);
        $this->assertNull($item->cancellation_reason);
    }

    public function test_dining_session_detail_renders_status_and_only_legal_actions(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $waiting = $this->item($session, OrderItemStatus::Waiting);
        $preparing = $this->item($session, OrderItemStatus::Preparing);
        $ready = $this->item($session, OrderItemStatus::Ready);
        $response = $this->actingAs($staff)->get(route('pos.dining-sessions.show', $session))->assertOk();
        $response->assertSee(route('pos.order-items.cancel-waiting', $waiting))
            ->assertDontSee(route('pos.order-items.cancel-preparing', $preparing))
            ->assertSee(route('pos.order-items.mark-served', $ready));
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        if ($employee) {
            Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Employee', 'status' => EmployeeStatus::Active]);
        }

        return $user;
    }

    private function diningSession(DiningSessionStatus $status = DiningSessionStatus::Active): DiningSession
    {
        $employee = Employee::query()->forceCreate(['employee_code' => 'O-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Opener', 'status' => EmployeeStatus::Active]);
        $table = RestaurantTable::query()->forceCreate(['code' => 'T-'.fake()->unique()->numberBetween(1, 999999), 'name' => 'Table', 'capacity' => 6, 'runtime_status' => RestaurantTableStatus::Occupied, 'is_active' => true]);

        return DiningSession::query()->forceCreate(['session_code' => 'DS-'.fake()->unique()->numberBetween(1, 999999), 'table_id' => $table->id, 'opened_by_employee_id' => $employee->id, 'status' => $status, 'started_at' => now(), 'guest_count' => 4]);
    }

    private function item(DiningSession $session, OrderItemStatus $status): OrderItem
    {
        $category = Category::query()->forceCreate(['name' => 'Category', 'slug' => 'c-'.fake()->unique()->numberBetween(1, 999999), 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1]);
        $product = Product::query()->forceCreate(['category_id' => $category->id, 'name' => 'Product', 'slug' => 'p-'.fake()->unique()->numberBetween(1, 999999), 'price' => 10000, 'status' => Product::STATUS_ACTIVE, 'is_available' => true]);
        $order = Order::query()->forceCreate(['order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999), 'dining_session_id' => $session->id, 'source' => 'staff', 'ordered_at' => now()]);

        return OrderItem::query()->forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Snapshot', 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000, 'status' => $status]);
    }
}
