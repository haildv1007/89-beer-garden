<?php

namespace Tests\Feature\POS;

use App\Enums\EmployeeStatus;
use App\Models\Category;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualOperationalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()
            ->forRole(Role::where('code', 'staff')->firstOrFail())
            ->create();
        Employee::query()->forceCreate([
            'user_id' => $user->id,
            'employee_code' => 'MANUAL-1',
            'name' => 'Nhân viên',
            'status' => EmployeeStatus::Active,
        ]);
        $this->actingAs($user);
    }

    public function test_pos_home_is_the_table_map(): void
    {
        $this->get(route('pos.home'))->assertOk()->assertSee('Sơ đồ bàn');
    }

    public function test_staff_can_create_a_manual_reservation(): void
    {
        $category = Category::query()->forceCreate([
            'name' => 'Món ăn',
            'slug' => 'manual-food',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $product = Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => 'Món đặt trước',
            'slug' => 'manual-preorder',
            'price' => 139000,
            'status' => Product::STATUS_ACTIVE,
            'is_available' => true,
        ]);
        $this->get(route('pos.reservations.create'))->assertOk()->assertSee('Thêm đặt bàn');
        $this->post(route('pos.reservations.store'), [
            'name' => 'Khách Zalo',
            'phone' => '0912345678',
            'reservation_date' => now()->addDay()->format('Y-m-d'),
            'reservation_time' => '18:30',
            'party_size' => 4,
            'note' => 'Đặt qua Zalo',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'note' => 'Ít cay']],
        ])->assertRedirect();
        $this->assertDatabaseHas('customers', ['name' => 'Khách Zalo']);
        $this->assertDatabaseHas('reservations', ['party_size' => 4, 'note' => 'Đặt qua Zalo', 'status' => 'pending']);
        $this->assertDatabaseHas('fulfillment_orders', ['fulfillment_type' => 'dine_in', 'subtotal' => 278000]);
        $this->assertDatabaseHas('fulfillment_order_items', [
            'product_name' => 'Món đặt trước',
            'quantity' => 2,
            'note' => 'Ít cay',
        ]);
    }

    public function test_staff_can_create_a_manual_pickup_order(): void
    {
        $category = Category::query()->forceCreate([
            'name' => 'Đồ uống',
            'slug' => 'manual-drink',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $product = Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => 'Nước ngọt',
            'slug' => 'manual-soft-drink',
            'price' => 25000,
            'status' => Product::STATUS_ACTIVE,
            'is_available' => true,
        ]);
        $this->get(route('pos.fulfillment-orders.create'))->assertOk()->assertSee('Tạo đơn ngoài quán');
        $this->post(route('pos.fulfillment-orders.store'), [
            'fulfillment_type' => 'pickup',
            'customer_name' => 'Khách Messenger',
            'phone' => '0987654321',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'note' => 'Đặt qua Messenger',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'note' => 'Ít đá',
                ],
            ],
        ])->assertRedirect();
        $order = FulfillmentOrder::query()->sole();
        $this->assertSame(FulfillmentOrder::STATUS_PENDING, $order->status);
        $this->assertSame($product->price * 2, $order->total_amount);
        $this->assertDatabaseHas('fulfillment_order_items', [
            'fulfillment_order_id' => $order->id,
            'quantity' => 2,
            'note' => 'Ít đá',
        ]);
    }
}
