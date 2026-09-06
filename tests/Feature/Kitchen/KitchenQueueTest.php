<?php

namespace Tests\Feature\Kitchen;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\KitchenTicket;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use App\Services\Order\CreateOrderService;
use App\Services\Order\UpdateWaitingOrderItemService;
use App\Services\OrderItem\CancelOrderItemService;
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

    public function test_sending_a_table_order_creates_one_snapshot_kitchen_ticket(): void
    {
        [$staff, $session, $product] = $this->operation();
        $order = app(CreateOrderService::class)->create(
            $session,
            $staff,
            [['product_id' => $product->id, 'quantity' => 2, 'note' => 'Không hành']],
            'Ra món cùng lúc',
        );

        $ticket = KitchenTicket::query()->sole();
        $this->assertSame(KitchenTicket::TYPE_ORDER, $ticket->type);
        $this->assertSame(KitchenTicket::STATUS_PENDING, $ticket->status);
        $this->assertSame($order->order_code, $ticket->payload['order_code']);
        $this->assertSame('Không hành', $ticket->payload['items'][0]['note']);

        app(CreateOrderService::class);
        $this->assertCount(1, $order->kitchenTickets);
    }

    public function test_kitchen_is_a_read_only_ticket_register_with_print_and_reprint(): void
    {
        [$staff, $session, $product] = $this->operation();
        app(CreateOrderService::class)->create(
            $session,
            $staff,
            [['product_id' => $product->id, 'quantity' => 1, 'note' => null]],
            null,
        );
        $ticket = KitchenTicket::query()->sole();
        $kitchen = $this->user('kitchen');

        $this->actingAs($kitchen)
            ->get(route('kitchen.home'))
            ->assertOk()
            ->assertSee($ticket->ticket_code)
            ->assertSee($product->name)
            ->assertSee('Mở In Tự Động')
            ->assertSee('In thủ công')
            ->assertDontSee('Nhận món')
            ->assertDontSee('Hoàn thành');
        $this->post(route('kitchen.tickets.print', $ticket))->assertRedirect(
            route('kitchen.tickets.printable', ['kitchenTicket' => $ticket, 'autoprint' => 1]),
        );
        $this->assertSame(KitchenTicket::STATUS_PRINTED, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->fresh()->print_attempts);
        $this->get(route('kitchen.tickets.printable', $ticket))
            ->assertOk()
            ->assertSee('PHIẾU BẾP')
            ->assertSee('KHÔNG PHẢI HÓA ĐƠN THANH TOÁN');
    }

    public function test_changing_and_cancelling_sent_items_create_separate_tickets(): void
    {
        [$staff, $session, $product] = $this->operation();
        $order = app(CreateOrderService::class)->create(
            $session,
            $staff,
            [['product_id' => $product->id, 'quantity' => 2, 'note' => 'Ít cay']],
            null,
        );
        $item = $order->items->first();
        app(UpdateWaitingOrderItemService::class)->update($item, $staff, 3, 'Không cay');
        app(CancelOrderItemService::class)->cancelWaiting($item->fresh(), $staff, 'Khách đổi món');

        $this->assertSame(
            [KitchenTicket::TYPE_ORDER, KitchenTicket::TYPE_ADJUSTMENT, KitchenTicket::TYPE_CANCELLATION],
            KitchenTicket::query()->oldest()->pluck('type')->all(),
        );
        $cancelTicket = KitchenTicket::query()->where('type', KitchenTicket::TYPE_CANCELLATION)->firstOrFail();
        $this->assertStringContainsString('Khách đổi món', $cancelTicket->payload['items'][0]['change']);
    }

    public function test_context_and_permission_protect_ticket_pages(): void
    {
        [$staff, $session, $product] = $this->operation();
        app(CreateOrderService::class)->create(
            $session,
            $staff,
            [['product_id' => $product->id, 'quantity' => 1]],
            null,
        );
        $ticket = KitchenTicket::query()->sole();

        $this->get(route('kitchen.home'))->assertRedirect(route('login'));
        $this->actingAs($staff)->get(route('kitchen.home'))->assertForbidden();
        $kitchen = $this->user('kitchen');
        $this->actingAs($kitchen)->get(route('kitchen.home'))->assertOk();
        $this->get(route('kitchen.tickets.printable', $ticket))->assertOk();
        $this->get(route('kitchen.autoprint'))->assertOk()->assertSee('IN TỰ ĐỘNG ĐANG BẬT');
        $this->getJson(route('kitchen.autoprint.next'))
            ->assertOk()
            ->assertJsonPath('ticket_id', $ticket->id)
            ->assertJsonPath('ticket_code', $ticket->ticket_code);
        $this->postJson(route('kitchen.autoprint.complete', $ticket))->assertOk();
        $this->assertSame(KitchenTicket::STATUS_PRINTED, $ticket->fresh()->status);
    }

    private function operation(): array
    {
        $staff = $this->user('staff');
        $table = RestaurantTable::query()->forceCreate([
            'code' => 'T-14',
            'name' => 'Bàn 14',
            'capacity' => 6,
            'runtime_status' => RestaurantTableStatus::Occupied,
            'is_active' => true,
        ]);
        $session = DiningSession::query()->forceCreate([
            'session_code' => 'DS-14',
            'table_id' => $table->id,
            'opened_by_employee_id' => $staff->employee->id,
            'status' => DiningSessionStatus::Active,
            'started_at' => now(),
            'guest_count' => 4,
        ]);
        $category = Category::query()->forceCreate([
            'name' => 'Món nướng',
            'slug' => 'mon-nuong',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);
        $product = Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => 'Ba chỉ nướng',
            'slug' => 'ba-chi-nuong',
            'price' => 139000,
            'status' => Product::STATUS_ACTIVE,
            'is_available' => true,
        ]);

        return [$staff, $session, $product];
    }

    private function user(string $role): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        Employee::query()->forceCreate([
            'user_id' => $user->id,
            'employee_code' => 'E-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Nhân viên',
            'status' => EmployeeStatus::Active,
        ]);

        return $user->load('employee');
    }
}
