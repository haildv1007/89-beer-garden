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

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_creates_multi_item_order_with_server_owned_snapshot(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $beer = $this->product('Cold Beer', 42000);
        $food = $this->product('Grilled Food', 125000);

        $this->actingAs($staff)
            ->post(route('pos.orders.store', $session), [
                'note' => 'First round',
                'items' => [
                    ['product_id' => $beer->id, 'quantity' => 3, 'note' => 'Very cold'],
                    ['product_id' => $food->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect(route('pos.dining-sessions.show', $session));

        $order = Order::query()->sole();
        $this->assertMatchesRegularExpression('/^GM-\d{6}-\d{4,}$/', $order->order_code);
        $this->assertSame($session->id, $order->dining_session_id);
        $this->assertSame($staff->employee->id, $order->created_by_employee_id);
        $this->assertNull($order->created_by_customer_id);
        $this->assertSame('staff', $order->source);
        $this->assertNotNull($order->ordered_at);
        $this->assertSame('First round', $order->note);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $beer->id,
            'product_name' => 'Cold Beer',
            'quantity' => 3,
            'unit_price' => 42000,
            'line_total' => 126000,
            'status' => 'waiting',
            'note' => 'Very cold',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $food->id,
            'product_name' => 'Grilled Food',
            'quantity' => 2,
            'unit_price' => 125000,
            'line_total' => 250000,
        ]);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_additional_order_is_new_order_in_same_session_using_current_price(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product('Beer', 30000);
        $this->actingAs($staff)->post(route('pos.orders.store', $session), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $first = OrderItem::query()->sole();

        $product->forceFill(['price' => 35000])->save();
        $this->post(route('pos.orders.store', $session), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect();

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('dining_sessions', 1);
        $this->assertSame(30000, $first->fresh()->unit_price);
        $this->assertDatabaseHas('order_items', ['unit_price' => 35000, 'quantity' => 2, 'line_total' => 70000]);
        $this->assertSame(1, Order::query()->distinct()->count('dining_session_id'));
    }

    public function test_session_and_table_invariants_are_rechecked(): void
    {
        $staff = $this->user('staff');
        $product = $this->product();
        $completed = $this->diningSession(DiningSessionStatus::Completed);
        $this->actingAs($staff)
            ->post(route('pos.orders.store', $completed), [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('dining_session');

        foreach (
            [RestaurantTableStatus::Available, RestaurantTableStatus::Reserved, RestaurantTableStatus::Cleaning] as $status
        ) {
            $session = $this->diningSession(DiningSessionStatus::Active, $status);
            $this->post(route('pos.orders.store', $session), [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])->assertSessionHasErrors('dining_session');
        }
        $session = $this->diningSession();
        $session->table->forceFill(['is_active' => false])->save();
        $this->post(route('pos.orders.store', $session), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('dining_session');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_empty_and_invalid_quantities_are_rejected(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product();
        $this->actingAs($staff)
            ->post(route('pos.orders.store', $session), ['items' => []])
            ->assertSessionHasErrors('items');
        foreach ([0, -1, 1001, 1.5, 'two'] as $quantity) {
            $this->post(route('pos.orders.store', $session), [
                'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
            ])->assertSessionHasErrors('items.0.quantity');
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_unavailable_deleted_and_inactive_category_products_are_rejected_atomically(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $valid = $this->product('Valid');
        $invalid = [
            $this->product('Inactive', 10000, Product::STATUS_INACTIVE),
            $this->product('Unavailable', 10000, Product::STATUS_ACTIVE, false),
        ];
        $deleted = $this->product('Deleted');
        $deleted->delete();
        $invalid[] = $deleted;
        $inactiveCategory = $this->category(Category::STATUS_INACTIVE);
        $invalid[] = $this->product('Inactive Category', 10000, Product::STATUS_ACTIVE, true, $inactiveCategory);

        foreach ($invalid as $product) {
            $this->actingAs($staff)
                ->post(route('pos.orders.store', $session), [
                    'items' => [
                        ['product_id' => $valid->id, 'quantity' => 1],
                        ['product_id' => $product->id, 'quantity' => 1],
                    ],
                ])
                ->assertSessionHasErrors('items');
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
        }
    }

    public function test_client_cannot_forge_order_or_item_integrity_fields(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product();
        $payload = [
            'order_code' => 'FORGED',
            'dining_session_id' => 999,
            'created_by_employee_id' => 999,
            'created_by_customer_id' => 999,
            'source' => 'customer',
            'ordered_at' => now(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'product_name' => 'Forged',
                    'unit_price' => 1,
                    'price' => 1,
                    'line_total' => 1,
                    'status' => 'served',
                    'cancelled_by_employee_id' => 999,
                    'cancelled_at' => now(),
                ],
            ],
        ];
        $this->actingAs($staff)
            ->post(route('pos.orders.store', $session), $payload)
            ->assertSessionHasErrors([
                'order_code',
                'dining_session_id',
                'created_by_employee_id',
                'created_by_customer_id',
                'source',
                'ordered_at',
                'items.0.product_name',
                'items.0.unit_price',
                'items.0.price',
                'items.0.line_total',
                'items.0.status',
                'items.0.cancelled_by_employee_id',
                'items.0.cancelled_at',
            ]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_item_insert_failure_rolls_back_order_and_every_item(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $first = $this->product('First');
        $second = $this->product('Second');
        $calls = 0;
        Event::listen('eloquent.creating: '.OrderItem::class, function () use (&$calls): void {
            if (++$calls === 2) {
                throw new RuntimeException('item failed');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($staff)->post(route('pos.orders.store', $session), [
                'items' => [
                    ['product_id' => $first->id, 'quantity' => 1],
                    ['product_id' => $second->id, 'quantity' => 1],
                ],
            ]);
            $this->fail('Expected item failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('item failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(RestaurantTableStatus::Occupied, $session->table->fresh()->runtime_status);
        $this->assertSame(DiningSessionStatus::Active, $session->fresh()->status);
    }

    public function test_only_waiting_item_in_active_session_can_be_modified_with_historical_price(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $product = $this->product('Snapshot', 25000);
        $this->actingAs($staff)->post(route('pos.orders.store', $session), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $item = OrderItem::query()->sole();
        $product->forceFill(['price' => 99000])->save();
        $this->patch(route('pos.order-items.update', $item), ['quantity' => 3, 'note' => 'Updated'])->assertRedirect();
        $item->refresh();
        $this->assertSame(3, $item->quantity);
        $this->assertSame(25000, $item->unit_price);
        $this->assertSame(75000, $item->line_total);
        $this->assertSame('Updated', $item->note);

        foreach (
            [OrderItemStatus::Preparing, OrderItemStatus::Ready, OrderItemStatus::Served, OrderItemStatus::Cancelled] as $status
        ) {
            $item->forceFill(['status' => $status])->save();
            $this->patch(route('pos.order-items.update', $item), ['quantity' => 2])->assertSessionHasErrors(
                'order_item',
            );
        }
        $item->forceFill(['status' => OrderItemStatus::Waiting])->save();
        $session->forceFill(['status' => DiningSessionStatus::Completed])->save();
        $this->patch(route('pos.order-items.update', $item), ['quantity' => 2])->assertSessionHasErrors('order_item');
    }

    public function test_update_rejects_zero_and_forged_fields(): void
    {
        $staff = $this->user('staff');
        $item = $this->orderItem($this->diningSession(), $staff->employee, $this->product());
        $this->actingAs($staff)
            ->patch(route('pos.order-items.update', $item), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');
        $this->patch(route('pos.order-items.update', $item), [
            'quantity' => 2,
            'product_id' => 999,
            'unit_price' => 1,
            'line_total' => 2,
            'status' => 'served',
        ])->assertSessionHasErrors(['product_id', 'unit_price', 'line_total', 'status']);
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_permissions_context_disabled_actors_and_blades_are_enforced(): void
    {
        $session = $this->diningSession();
        $product = $this->product('Visible Product');
        $this->get(route('pos.orders.create', $session))->assertRedirect(route('login'));
        $this->actingAs($this->user('kitchen'))->get(route('pos.orders.create', $session))->assertForbidden();
        $this->actingAs($this->user('customer', false))->get(route('pos.orders.create', $session))->assertForbidden();
        foreach (['staff', 'manager', 'admin'] as $role) {
            $this->actingAs($this->user($role))->get(route('pos.orders.create', $session))->assertOk();
        }

        $staff = $this->user('staff');
        $staff->role->permissions()->detach(Permission::where('code', 'dining-session.view')->firstOrFail());
        $this->actingAs($staff)->get(route('pos.orders.create', $session))->assertForbidden();
        $staff->role->permissions()->attach(Permission::where('code', 'dining-session.view')->firstOrFail());
        $staff->role->permissions()->detach(Permission::where('code', 'order.create')->firstOrFail());
        $this->actingAs($staff)
            ->post(route('pos.orders.store', $session), ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertForbidden();
        $staff->role->permissions()->attach(Permission::where('code', 'order.create')->firstOrFail());
        $item = $this->orderItem($session, $staff->employee, $product);
        $staff->role->permissions()->detach(Permission::where('code', 'order.update')->firstOrFail());
        $this->patch(route('pos.order-items.update', $item), ['quantity' => 2])->assertForbidden();

        $staff->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->get(route('pos.dining-sessions.show', $session))->assertForbidden();
        $staff->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->get(route('pos.home'))->assertRedirect(route('login'));
    }

    public function test_detail_renders_all_order_rounds_and_only_valid_products(): void
    {
        $staff = $this->user('staff');
        $session = $this->diningSession();
        $valid = $this->product('Orderable');
        $invalid = $this->product('Hidden unavailable', 10000, Product::STATUS_ACTIVE, false);
        $this->actingAs($staff)
            ->get(route('pos.orders.create', $session))
            ->assertOk()
            ->assertSee('Orderable')
            ->assertDontSee($invalid->name);
        $this->post(route('pos.orders.store', $session), ['items' => [['product_id' => $valid->id, 'quantity' => 1]]]);
        $this->post(route('pos.orders.store', $session), ['items' => [['product_id' => $valid->id, 'quantity' => 2]]]);
        $codes = Order::query()->pluck('order_code');
        $response = $this->get(route('pos.dining-sessions.show', $session))->assertOk();
        foreach ($codes as $code) {
            $response->assertSee($code);
        }
    }

    private function category(string $status = Category::STATUS_ACTIVE): Category
    {
        return Category::query()->forceCreate([
            'name' => 'Category '.fake()->unique()->numberBetween(1, 999999),
            'slug' => 'category-'.fake()->unique()->numberBetween(1, 999999),
            'status' => $status,
            'sort_order' => 1,
        ]);
    }

    private function product(
        string $name = 'Product',
        int $price = 10000,
        string $status = Product::STATUS_ACTIVE,
        bool $available = true,
        ?Category $category = null,
    ): Product {
        return Product::query()->forceCreate([
            'category_id' => ($category ?? $this->category())->id,
            'name' => $name,
            'slug' => 'product-'.fake()->unique()->numberBetween(1, 999999),
            'price' => $price,
            'status' => $status,
            'is_available' => $available,
        ]);
    }

    private function diningSession(
        DiningSessionStatus $status = DiningSessionStatus::Active,
        RestaurantTableStatus $tableStatus = RestaurantTableStatus::Occupied,
    ): DiningSession {
        $employee = Employee::query()->forceCreate([
            'employee_code' => 'OPEN-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Opener',
            'status' => EmployeeStatus::Active,
        ]);
        $table = RestaurantTable::query()->forceCreate([
            'code' => 'T-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Table',
            'capacity' => 6,
            'runtime_status' => $tableStatus,
            'is_active' => true,
        ]);

        return DiningSession::query()->forceCreate([
            'session_code' => 'DS-'.fake()->unique()->numberBetween(1, 999999),
            'table_id' => $table->id,
            'opened_by_employee_id' => $employee->id,
            'status' => $status,
            'started_at' => now(),
            'guest_count' => 4,
        ]);
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        if ($employee) {
            Employee::query()->forceCreate([
                'user_id' => $user->id,
                'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999),
                'name' => 'Employee',
                'status' => EmployeeStatus::Active,
            ]);
        }

        return $user;
    }

    private function orderItem(DiningSession $session, Employee $employee, Product $product): OrderItem
    {
        $order = Order::query()->forceCreate([
            'order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id,
            'created_by_employee_id' => $employee->id,
            'source' => 'staff',
            'ordered_at' => now(),
        ]);

        return OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $product->price,
            'line_total' => $product->price,
            'status' => OrderItemStatus::Waiting,
        ]);
    }
}
