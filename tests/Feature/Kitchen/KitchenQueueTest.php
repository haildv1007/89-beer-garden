<?php

namespace Tests\Feature\Kitchen;

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
use Tests\TestCase;

class KitchenQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_queue_only_shows_processable_items_from_active_sessions_using_snapshots(): void
    {
        $kitchen = $this->user('kitchen');
        $active = $this->diningSession();
        $completed = $this->diningSession(DiningSessionStatus::Completed);
        $waiting = $this->item($active, OrderItemStatus::Waiting, 'Historical Beer', 'No ice');
        $preparing = $this->item($active, OrderItemStatus::Preparing, 'Historical Grill');
        $ready = $this->item($active, OrderItemStatus::Ready, 'Historical Soup');
        $this->item($active, OrderItemStatus::Served, 'Served Hidden');
        $this->item($active, OrderItemStatus::Cancelled, 'Cancelled Hidden');
        $this->item($completed, OrderItemStatus::Waiting, 'Completed Hidden');
        $waiting->product->forceFill(['name' => 'Changed Product', 'status' => Product::STATUS_INACTIVE])->save();
        $waiting->product->delete();

        $response = $this->actingAs($kitchen)->get(route('kitchen.home'))->assertOk();
        foreach ([$waiting, $preparing, $ready] as $item) {
            $response->assertSee($item->product_name)->assertSee($item->order->order_code);
        }
        $response->assertSee('No ice')->assertSee($waiting->order->diningSession->table->code)
            ->assertDontSee('Changed Product')->assertDontSee('Served Hidden')
            ->assertDontSee('Cancelled Hidden')->assertDontSee('Completed Hidden');
    }

    public function test_kitchen_performs_only_waiting_to_preparing_and_preparing_to_ready(): void
    {
        $kitchen = $this->user('kitchen');
        $waiting = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $snapshot = [$waiting->quantity, $waiting->unit_price, $waiting->line_total, $waiting->product_name];
        $this->actingAs($kitchen)->patch(route('kitchen.order-items.start-preparing', $waiting))->assertRedirect();
        $this->assertSame(OrderItemStatus::Preparing, $waiting->fresh()->status);
        $this->patch(route('kitchen.order-items.mark-ready', $waiting))->assertRedirect();
        $waiting->refresh();
        $this->assertSame(OrderItemStatus::Ready, $waiting->status);
        $this->assertSame($snapshot, [$waiting->quantity, $waiting->unit_price, $waiting->line_total, $waiting->product_name]);

        $this->patch(route('kitchen.order-items.mark-ready', $waiting))->assertSessionHasErrors('order_item');
        $otherWaiting = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->patch(route('kitchen.order-items.mark-ready', $otherWaiting))->assertSessionHasErrors('order_item');
        $preparing = $this->item($this->diningSession(), OrderItemStatus::Preparing);
        $this->patch(route('kitchen.order-items.start-preparing', $preparing))->assertSessionHasErrors('order_item');
    }

    public function test_completed_session_and_forged_fields_are_rejected(): void
    {
        $kitchen = $this->user('kitchen');
        $item = $this->item($this->diningSession(DiningSessionStatus::Completed), OrderItemStatus::Waiting);
        $this->actingAs($kitchen)->patch(route('kitchen.order-items.start-preparing', $item))->assertSessionHasErrors('order_item');
        $active = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->patch(route('kitchen.order-items.start-preparing', $active), [
            'status' => 'ready', 'quantity' => 99, 'unit_price' => 1, 'line_total' => 1,
            'cancelled_by_employee_id' => 999, 'cancelled_at' => now(), 'cancellation_reason' => 'forged',
        ])->assertSessionHasErrors(['status', 'quantity', 'unit_price', 'line_total', 'cancelled_by_employee_id', 'cancelled_at', 'cancellation_reason']);
        $this->assertSame(OrderItemStatus::Waiting, $active->fresh()->status);
    }

    public function test_kitchen_context_queue_and_each_action_permission_are_independent(): void
    {
        $item = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $this->get(route('kitchen.home'))->assertRedirect(route('login'));
        $this->actingAs($this->user('customer', false))->get(route('kitchen.home'))->assertForbidden();
        $staff = $this->user('staff');
        $this->actingAs($staff)->get(route('kitchen.home'))->assertForbidden();
        $this->patch(route('kitchen.order-items.start-preparing', $item))->assertForbidden();
        $preparingForStaff = $this->item($this->diningSession(), OrderItemStatus::Preparing);
        $this->patch(route('kitchen.order-items.mark-ready', $preparingForStaff))->assertForbidden();

        $kitchen = $this->user('kitchen');
        $kitchen->role->permissions()->detach(Permission::where('code', 'kitchen.queue.view')->firstOrFail());
        $this->actingAs($kitchen)->get(route('kitchen.home'))->assertForbidden();
        $kitchen->role->permissions()->attach(Permission::where('code', 'kitchen.queue.view')->firstOrFail());

        foreach (['order-item.mark-preparing' => 'kitchen.order-items.start-preparing', 'order-item.mark-ready' => 'kitchen.order-items.mark-ready'] as $permission => $route) {
            $record = $route === 'kitchen.order-items.start-preparing' ? $item : $this->item($this->diningSession(), OrderItemStatus::Preparing);
            $model = Permission::where('code', $permission)->firstOrFail();
            $kitchen->role->permissions()->detach($model);
            $this->actingAs($kitchen)->patch(route($route, $record))->assertForbidden();
            $kitchen->role->permissions()->attach($model);
        }
        $kitchen->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->actingAs($kitchen)->get(route('kitchen.home'))->assertForbidden();
        $kitchen->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->get(route('kitchen.home'))->assertRedirect(route('login'));
    }

    public function test_kitchen_navigation_and_action_visibility_follow_permissions(): void
    {
        $kitchen = $this->user('kitchen');
        $waiting = $this->item($this->diningSession(), OrderItemStatus::Waiting);
        $response = $this->actingAs($kitchen)->get(route('kitchen.home'))->assertOk()
            ->assertSee(route('kitchen.home'))->assertSee(route('kitchen.order-items.start-preparing', $waiting));
        $response->assertDontSee('cancel-waiting')->assertDontSee('served');
        $kitchen->role->permissions()->detach(Permission::where('code', 'order-item.mark-preparing')->firstOrFail());
        $this->get(route('kitchen.home'))->assertOk()->assertDontSee(route('kitchen.order-items.start-preparing', $waiting));
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

    private function item(DiningSession $session, OrderItemStatus $status, string $name = 'Snapshot Product', ?string $note = null): OrderItem
    {
        $category = Category::query()->forceCreate(['name' => 'Category', 'slug' => 'c-'.fake()->unique()->numberBetween(1, 999999), 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1]);
        $product = Product::query()->forceCreate(['category_id' => $category->id, 'name' => 'Current Product', 'slug' => 'p-'.fake()->unique()->numberBetween(1, 999999), 'price' => 10000, 'status' => Product::STATUS_ACTIVE, 'is_available' => true]);
        $order = Order::query()->forceCreate(['order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999), 'dining_session_id' => $session->id, 'source' => 'staff', 'ordered_at' => now()]);

        return OrderItem::query()->forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $name, 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000, 'status' => $status, 'note' => $note]);
    }
}
