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

class FulfillmentPaymentTest extends TestCase
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
            'employee_code' => 'PAY-1',
            'name' => 'Thu ngân',
            'status' => EmployeeStatus::Active,
        ]);
        $this->actingAs($user);
    }

    public function test_pay_on_receipt_order_can_be_collected_in_cash_and_printed(): void
    {
        $order = $this->createOrder('pickup', 'pay_on_receipt');
        $this->post(route('pos.fulfillment-orders.payment.complete', $order), [
            'payment_method' => 'cash',
            'cash_received' => 200000,
            'confirmed_received' => '1',
        ])->assertRedirect(route('pos.fulfillment-orders.invoice', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame(61000, $order->change_amount);
        $this->get(route('pos.fulfillment-orders.invoice', $order))
            ->assertOk()
            ->assertSee('HÓA ĐƠN BÁN HÀNG')
            ->assertSee('Tiền trả lại');
    }

    public function test_pay_on_receipt_order_can_be_collected_by_bank_transfer(): void
    {
        $order = $this->createOrder('delivery', 'pay_on_receipt');
        $this->post(route('pos.fulfillment-orders.payment.complete', $order), [
            'payment_method' => 'bank_transfer',
            'confirmed_received' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('fulfillment_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'received_amount' => null,
            'change_amount' => null,
        ]);
    }

    public function test_bank_transfer_order_cannot_be_collected_as_cash(): void
    {
        $order = $this->createOrder('delivery', 'bank_transfer');
        $this->from(route('pos.fulfillment-orders.show', $order))
            ->post(route('pos.fulfillment-orders.payment.complete', $order), [
                'payment_method' => 'cash',
                'cash_received' => 200000,
                'confirmed_received' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('payment_method');
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_paid_order_cannot_be_paid_again_or_rejected(): void
    {
        $order = $this->createOrder('pickup', 'pay_on_receipt');
        $payload = ['payment_method' => 'cash', 'cash_received' => 139000, 'confirmed_received' => '1'];
        $this->post(route('pos.fulfillment-orders.payment.complete', $order), $payload)->assertRedirect();
        $this->from(route('pos.fulfillment-orders.show', $order))
            ->post(route('pos.fulfillment-orders.payment.complete', $order), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('payment');
        $this->from(route('pos.fulfillment-orders.show', $order))
            ->patch(route('pos.fulfillment-orders.reject', $order), ['reason' => 'Khách hủy'])
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }

    public function test_unpaid_order_has_no_invoice(): void
    {
        $order = $this->createOrder('pickup', 'pay_on_receipt');
        $this->get(route('pos.fulfillment-orders.invoice', $order))->assertNotFound();
    }

    private function createOrder(string $type, string $paymentOption): FulfillmentOrder
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'payment-food'],
            ['name' => 'Món ăn', 'status' => 'active', 'sort_order' => 1],
        );
        $product = Product::query()->firstOrCreate(
            ['slug' => 'payment-dish'],
            [
                'category_id' => $category->id,
                'name' => 'Ba chỉ nướng',
                'price' => 139000,
                'status' => Product::STATUS_ACTIVE,
                'is_available' => true,
            ],
        );
        $payload = [
            'fulfillment_type' => $type,
            'payment_option' => $paymentOption,
            'customer_name' => 'Khách ngoài quán',
            'phone' => '0912345678',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'note' => null]],
        ];
        if ($type === 'delivery') {
            $payload['delivery_address'] = '12 Nguyễn Trãi, Hà Nội';
        }
        $this->post(route('pos.fulfillment-orders.store'), $payload)->assertRedirect();

        return FulfillmentOrder::query()->latest('id')->firstOrFail();
    }
}
