<?php

namespace Tests\Feature\Customer;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\CustomerOrder\CustomerDiningContextService;
use App\Services\CustomerOrder\UniversalCartCheckoutService;
use App\Services\SystemSetting\SystemSettingCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

class CustomerSelfOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pos_creates_temporary_signed_link_only_with_permissions_capability_and_valid_session(): void
    {
        $session = $this->diningSession();
        $staff = $this->user('staff');
        $this->actingAs($staff)
            ->post(route('pos.dining-sessions.customer-access-link', $session))
            ->assertSessionHasErrors('context');
        $this->enableOrdering();
        $response = $this->post(route('pos.dining-sessions.customer-access-link', $session))->assertRedirect();
        $url = $response->getSession()->get('customer_access_url');
        $this->assertIsString($url);
        $this->assertTrue(URL::hasValidSignature(Request::create($url)));

        $staff->role->permissions()->detach(Permission::where('code', 'order.create')->firstOrFail());
        $this->post(route('pos.dining-sessions.customer-access-link', $session))->assertForbidden();
        $staff->role->permissions()->attach(Permission::where('code', 'order.create')->firstOrFail());
        $staff->role->permissions()->detach(Permission::where('code', 'dining-session.view')->firstOrFail());
        $this->post(route('pos.dining-sessions.customer-access-link', $session))->assertForbidden();
        $staff->role->permissions()->attach(Permission::where('code', 'dining-session.view')->firstOrFail());
        $session->forceFill(['status' => DiningSessionStatus::Completed])->save();
        $this->post(route('pos.dining-sessions.customer-access-link', $session))->assertSessionHasErrors('context');
    }

    public function test_capability_missing_disabled_wrong_type_and_invalid_value_fail_closed(): void
    {
        $session = $this->diningSession();
        foreach (
            [
                null,
                ['value' => 'false', 'type' => 'boolean'],
                ['value' => 'true', 'type' => 'string'],
                ['value' => '1', 'type' => 'boolean'],
            ] as $setting
        ) {
            SystemSetting::query()->delete();
            if ($setting !== null) {
                SystemSetting::query()->forceCreate(['key' => 'customer_ordering_enabled'] + $setting);
            }
            $this->get($this->signedUrl($session))->assertSessionHasErrors('context');
            $this->withSession([CustomerDiningContextService::SESSION_KEY => ['dining_session_id' => $session->id]])
                ->get(route('customer.cart.index'))
                ->assertOk();
            $this->assertNull(session(CustomerDiningContextService::SESSION_KEY));
        }
    }

    public function test_invalid_tampered_expired_url_is_rejected_and_valid_url_binds_context(): void
    {
        $this->enableOrdering();
        $session = $this->diningSession();
        $this->get(route('customer.dining-context.bind', $session))
            ->assertForbidden()
            ->assertSee(__('errors.dining_link_hint'));
        $other = $this->diningSession();
        $tampered = str_replace('/'.$session->id.'?', '/'.$other->id.'?', $this->signedUrl($session));
        $this->get($tampered)->assertForbidden();
        $this->get($this->signedUrl($session, now()->subMinute()))->assertForbidden();

        $oldId = session()->getId();
        $this->get($this->signedUrl($session))->assertRedirect(route('customer.dining-context.confirmation'));
        $this->assertSame($session->id, session(CustomerDiningContextService::SESSION_KEY.'.dining_session_id'));
        $this->assertNotSame($oldId, session()->getId());
        $this->get(route('customer.dining-context.confirmation'))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee($session->table->code)
            ->assertSee(__('customer_ui.dining_active'))
            ->assertSee(route('customer.menu.index'))
            ->assertSee(route('customer.cart.index'));
    }

    public function test_completed_or_invalid_table_session_cannot_bind(): void
    {
        $this->enableOrdering();
        $completed = $this->diningSession(DiningSessionStatus::Completed);
        $this->get($this->signedUrl($completed))->assertSessionHasErrors('context');
        foreach ([RestaurantTableStatus::Available, RestaurantTableStatus::Cleaning] as $status) {
            $session = $this->diningSession(DiningSessionStatus::Active, $status);
            $this->get($this->signedUrl($session))->assertSessionHasErrors('context');
        }
    }

    public function test_cart_add_merge_update_note_remove_clear_and_stores_no_price(): void
    {
        $session = $this->bindGuest();
        $product = $this->product('Beer', 30000);
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'note' => 'Cold',
        ])->assertRedirect();
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();
        $stored = session(CustomerCartService::SESSION_KEY.'.'.$product->id);
        $this->assertSame(['product_id' => $product->id, 'quantity' => 3, 'note' => 'Cold'], $stored);
        $this->assertArrayNotHasKey('price', $stored);
        $this->assertArrayNotHasKey('product_name', $stored);
        $this->patch(route('customer.cart.items.update', $product->id), [
            'quantity' => 4,
            'note' => 'No ice',
        ])->assertRedirect();
        $this->patchJson(route('customer.cart.items.update', $product->id), ['quantity' => 5, 'note' => 'No ice'])
            ->assertOk()
            ->assertJsonPath('quantity', 5)
            ->assertJsonPath('line_total', 150000)
            ->assertJsonPath('cart_count', 5)
            ->assertJsonPath('summary.total', 150000);
        $this->get(route('customer.cart.index'))->assertOk()->assertSee($session->session_code)->assertSee('No ice');
        $this->delete(route('customer.cart.items.destroy', $product->id))->assertRedirect();
        $this->assertSame([], session(CustomerCartService::SESSION_KEY));
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->delete(route('customer.cart.clear'))->assertRedirect();
        $this->assertNull(session(CustomerCartService::SESSION_KEY));
    }

    public function test_mini_cart_supports_ajax_add_update_and_remove(): void
    {
        $product = $this->product('Mini cart beer', 32000);

        $this->postJson(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('cart_count', 2)
            ->assertJsonPath('subtotal', 64000)
            ->assertJsonPath('items.0.name', 'Mini cart beer')
            ->assertJsonPath('items.0.quantity', 2);

        $this->getJson(route('customer.cart.mini'))
            ->assertOk()
            ->assertJsonPath('cart_count', 2)
            ->assertJsonPath('items.0.product_id', $product->id);

        $this->get(route('customer.menu.index'))
            ->assertOk()
            ->assertSee('data-mini-cart-floating', false)
            ->assertSee('data-mini-cart-backdrop', false)
            ->assertSee('data-mini-cart-subtotal', false);

        $this->patchJson(route('customer.cart.items.update', $product->id), ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('mini_cart.cart_count', 3)
            ->assertJsonPath('mini_cart.subtotal', 96000);

        $this->deleteJson(route('customer.cart.items.destroy', $product->id))
            ->assertOk()
            ->assertJsonPath('cart_count', 0)
            ->assertJsonPath('subtotal', 0)
            ->assertJsonCount(0, 'items');
    }

    public function test_universal_cart_survives_switching_dining_context(): void
    {
        $this->enableOrdering();
        $first = $this->diningSession();
        $second = $this->diningSession();
        $product = $this->product();
        $this->get($this->signedUrl($first));
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->get($this->signedUrl($second));
        $this->assertSame($second->id, session(CustomerDiningContextService::SESSION_KEY.'.dining_session_id'));
        $this->assertSame(2, session(CustomerCartService::SESSION_KEY.'.'.$product->id.'.quantity'));
    }

    public function test_arbitrary_context_and_forged_cart_or_order_fields_are_rejected(): void
    {
        $this->enableOrdering();
        $session = $this->diningSession();
        $product = $this->product();
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'dining_session_id' => $session->id,
        ])->assertSessionHasErrors('dining_session_id');
        $this->get(route('customer.cart.index', ['dining_session_id' => $session->id]))->assertOk();
        $this->assertNull(session(CustomerDiningContextService::SESSION_KEY));

        $this->get($this->signedUrl($session));
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('customer.cart.submit'), [
            'items' => [['product_id' => $product->id]],
            'dining_session_id' => 999,
            'customer_id' => 999,
            'created_by_customer_id' => 999,
            'created_by_employee_id' => 999,
            'source' => 'staff',
            'price' => 1,
            'product_name' => 'Forged',
            'status' => 'served',
        ])->assertSessionHasErrors([
            'items',
            'dining_session_id',
            'customer_id',
            'created_by_customer_id',
            'created_by_employee_id',
            'source',
            'price',
            'product_name',
            'status',
        ]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertNotNull(session(CustomerCartService::SESSION_KEY));
    }

    public function test_product_rules_apply_on_add_and_are_revalidated_on_submit(): void
    {
        $this->bindGuest();
        foreach (
            [
                $this->product('Inactive', 10000, Product::STATUS_INACTIVE),
                $this->product('Unavailable', 10000, Product::STATUS_ACTIVE, false),
                $this->product('Bad Category', 10000, Product::STATUS_ACTIVE, true, Category::STATUS_INACTIVE),
            ] as $product
        ) {
            $this->post(route('customer.cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])->assertSessionHasErrors('product');
        }
        $deleted = $this->product('Deleted');
        $deleted->delete();
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $deleted->id,
            'quantity' => 1,
        ])->assertNotFound();

        $valid = $this->product('Changed Later');
        $this->post(route('customer.cart.items.store'), ['product_id' => $valid->id, 'quantity' => 1]);
        $valid->forceFill(['is_available' => false])->save();
        $this->post(route('customer.cart.submit'))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
        $this->assertNotNull(session(CustomerCartService::SESSION_KEY));
    }

    public function test_empty_invalid_quantity_and_tampered_cart_are_rejected(): void
    {
        $this->bindGuest();
        $product = $this->product();
        $this->post(route('customer.cart.submit'))->assertSessionHasErrors('cart');
        foreach ([0, -1, 1001, 1.5, 'two'] as $quantity) {
            $this->post(route('customer.cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ])->assertSessionHasErrors('quantity');
        }
        $tampered = [(string) $product->id => ['product_id' => 999, 'quantity' => 1, 'price' => 1]];
        $this->withSession([CustomerCartService::SESSION_KEY => $tampered])
            ->get(route('customer.cart.index'))
            ->assertSessionHasErrors('cart');
        $this->withSession([CustomerCartService::SESSION_KEY => $tampered])
            ->post(route('customer.cart.submit'))
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_submit_creates_customer_source_waiting_snapshot_and_clears_cart(): void
    {
        $session = $this->bindGuest();
        $product = $this->product('Snapshot Beer', 42000);
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'note' => 'Cold',
        ]);
        $this->post(route('customer.cart.submit'), ['note' => 'Guest round'])->assertRedirect(
            route('customer.cart.success'),
        );
        $order = Order::query()->sole();
        $this->assertSame($session->id, $order->dining_session_id);
        $this->assertSame('customer', $order->source);
        $this->assertNull($order->created_by_employee_id);
        $this->assertNull($order->created_by_customer_id);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Snapshot Beer',
            'quantity' => 3,
            'unit_price' => 42000,
            'line_total' => 126000,
            'status' => 'waiting',
            'note' => 'Cold',
        ]);
        $this->assertNull(session(CustomerCartService::SESSION_KEY));
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_authenticated_customer_uses_own_profile_and_internal_actor_is_denied(): void
    {
        $this->enableOrdering();
        $session = $this->diningSession();
        $customerUser = $this->user('customer', false);
        $customer = Customer::query()->forceCreate(['user_id' => $customerUser->id, 'name' => 'Own Customer']);
        $this->actingAs($customerUser)->get($this->signedUrl($session))->assertRedirect();
        $product = $this->product();
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('customer.cart.submit'))->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'source' => 'customer',
            'created_by_customer_id' => $customer->id,
            'created_by_employee_id' => null,
        ]);

        $internal = $this->user('staff');
        $this->actingAs($internal)->get($this->signedUrl($session))->assertSessionHasErrors('context');
    }

    public function test_additional_order_is_new_order_same_session_with_current_price(): void
    {
        $session = $this->bindGuest();
        $product = $this->product('Beer', 30000);
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('customer.cart.submit'));
        $product->forceFill(['price' => 35000])->save();
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('customer.cart.submit'));
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('dining_sessions', 1);
        $this->assertSame([30000, 35000], OrderItem::query()->orderBy('id')->pluck('unit_price')->all());
        $this->assertSame(1, Order::query()->distinct()->count('dining_session_id'));
        $this->assertSame($session->id, Order::query()->latest('id')->value('dining_session_id'));
    }

    public function test_submit_failure_rolls_back_everything_and_preserves_cart(): void
    {
        $this->bindGuest();
        $first = $this->product('First');
        $second = $this->product('Second');
        $this->post(route('customer.cart.items.store'), ['product_id' => $first->id, 'quantity' => 1]);
        $this->post(route('customer.cart.items.store'), ['product_id' => $second->id, 'quantity' => 1]);
        $calls = 0;
        Event::listen('eloquent.creating: '.OrderItem::class, function () use (&$calls): void {
            if (++$calls === 2) {
                throw new RuntimeException('item failed');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->post(route('customer.cart.submit'));
            $this->fail('Expected item failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('item failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertCount(2, session(CustomerCartService::SESSION_KEY));
    }

    public function test_completed_context_before_submit_is_invalidated_and_status_is_idor_safe(): void
    {
        $session = $this->bindGuest();
        $product = $this->product('Own Session Item');
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $other = $this->diningSession();
        $this->customerOrder($other, 'Other Session Secret');
        $this->get(route('customer.orders.current', ['dining_session_id' => $other->id]))
            ->assertOk()
            ->assertDontSee('Other Session Secret');

        $session->forceFill(['status' => DiningSessionStatus::Completed])->save();
        $this->post(route('customer.cart.submit'))->assertSessionHasErrors('context');
        $this->assertDatabaseCount('orders', 1);
        $this->assertNull(session(CustomerDiningContextService::SESSION_KEY));
        $this->assertSame(1, session(CustomerCartService::SESSION_KEY.'.'.$product->id.'.quantity'));
    }

    public function test_disabled_account_is_blocked_and_customer_cannot_use_internal_item_actions(): void
    {
        $this->enableOrdering();
        $session = $this->diningSession();
        $user = $this->user('customer', false);
        Customer::query()->forceCreate(['user_id' => $user->id, 'name' => 'Customer']);
        $user->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->actingAs($user)->get($this->signedUrl($session))->assertRedirect(route('login'));

        $item = $this->customerOrder($session, 'Safe')->items()->firstOrFail();
        $active = $this->user('customer', false);
        Customer::query()->forceCreate(['user_id' => $active->id, 'name' => 'Active']);
        $this->actingAs($active)->patch(route('pos.order-items.mark-served', $item))->assertForbidden();
        $this->patch(route('kitchen.order-items.start-preparing', $item))->assertForbidden();
    }

    public function test_customer_order_enters_existing_kitchen_waiting_pipeline_and_views_render(): void
    {
        $session = $this->bindGuest();
        $product = $this->product('Kitchen Visible');
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->get(route('customer.menu.index'))->assertOk()->assertSee(route('customer.cart.items.store'));
        $this->get(route('customer.products.show', $product))
            ->assertOk()
            ->assertSee(route('customer.cart.items.store'));
        $this->get(route('customer.cart.index'))->assertOk();
        $this->post(route('customer.cart.submit'));
        $this->get(route('customer.cart.success'))->assertOk();
        $this->get(route('customer.orders.current'))->assertOk()->assertSee('Kitchen Visible');
        $kitchen = $this->user('kitchen');
        $this->actingAs($kitchen)->get(route('kitchen.home'))->assertOk()->assertSee('Kitchen Visible');
        $this->assertSame($session->id, Order::query()->sole()->dining_session_id);
    }

    public function test_guest_can_build_cart_without_context_select_fulfillment_and_preview_voucher(): void
    {
        $this->enableOrdering();
        $product = $this->product('Universal Cart Item');

        $this->get(route('customer.menu.index'))->assertOk()->assertSee(route('customer.cart.items.store'));
        $this->get(route('customer.products.show', $product))
            ->assertOk()
            ->assertSee(route('customer.cart.items.store'));
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get(route('customer.cart.index'))
            ->assertOk()
            ->assertSee('Universal Cart Item')
            ->assertSee(__('customer_order.fulfillment.at_table'))
            ->assertSee(__('customer_order.fulfillment.at_table_handoff'))
            ->assertSee(__('customer_order.fulfillment.dine_in'))
            ->assertSee(__('customer_order.fulfillment.pickup'))
            ->assertSee(__('customer_order.fulfillment.delivery'));

        $this->postJson(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'at_table'])
            ->assertOk()
            ->assertJsonPath('fulfillment_type', 'at_table')
            ->assertJsonPath('summary.shipping_fee', 0);
        $this->assertSame('at_table', session(UniversalCartCheckoutService::FULFILLMENT_KEY));
        $this->post(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'pickup'])->assertRedirect();
        $this->assertSame('pickup', session(UniversalCartCheckoutService::FULFILLMENT_KEY));
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::DELIVERY_FEE,
            'value' => '30000',
            'type' => 'integer',
        ]);
        $this->postJson(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'delivery'])
            ->assertOk()
            ->assertJsonPath('fulfillment_type', 'delivery')
            ->assertJsonPath('summary.shipping_fee', 30000)
            ->assertJsonPath('summary.total', 50000);
        $this->assertSame('delivery', session(UniversalCartCheckoutService::FULFILLMENT_KEY));

        Voucher::query()->forceCreate([
            'code' => 'CART10',
            'name' => 'Cart preview',
            'discount_type' => Voucher::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'usage_limit' => null,
            'used_count' => 0,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
        $this->post(route('customer.cart.voucher.apply'), ['voucher_code' => 'cart10'])->assertRedirect();
        $this->get(route('customer.cart.index'))->assertOk()->assertSee('CART10')->assertSee('48.000 ₫');

        $this->assertSame(2, session(CustomerCartService::SESSION_KEY.'.'.$product->id.'.quantity'));
        $this->assertNull(app('router')->getRoutes()->getByName('customer.dining-context.bind'));
    }

    public function test_guest_places_pickup_order_from_universal_cart_with_server_owned_totals(): void
    {
        $this->enableOrdering();
        $product = $this->product('Pickup Snapshot', 45000);
        $this->post(route('customer.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'note' => 'Less ice',
        ]);
        $this->post(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'pickup']);

        $this->get(route('customer.cart.checkout'))->assertOk()->assertSee('Pickup Snapshot');
        $this->post(route('customer.cart.checkout.store'), [
            'customer_name' => 'Guest Pickup',
            'phone' => '0901234567',
            'email' => 'guest@example.test',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'note' => 'Call me',
            'total_amount' => 1,
            'status' => 'completed',
            'fulfillment_type' => 'delivery',
        ])->assertSessionHasErrors(['total_amount', 'status', 'fulfillment_type']);

        $response = $this->post(route('customer.cart.checkout.store'), [
            'customer_name' => 'Guest Pickup',
            'phone' => '0901234567',
            'email' => 'guest@example.test',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'note' => 'Call me',
        ])->assertRedirect(route('customer.cart.checkout.success'));

        $order = FulfillmentOrder::with('items')->sole();
        $this->assertSame('pickup', $order->fulfillment_type);
        $this->assertSame('pending', $order->status);
        $this->assertSame(90000, $order->subtotal);
        $this->assertSame(90000, $order->total_amount);
        $this->assertSame('Pickup Snapshot', $order->items->sole()->product_name);
        $this->assertSame(2, $order->items->sole()->quantity);
        $this->assertNull(session(CustomerCartService::SESSION_KEY));
        $response->assertSessionHas('fulfillment_order_code', $order->order_code);
    }

    public function test_pos_confirmation_creates_printable_pickup_kitchen_ticket(): void
    {
        $this->enableOrdering();
        $product = $this->product('Pickup Kitchen Item', 32000);
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'pickup']);
        $this->post(route('customer.cart.checkout.store'), [
            'customer_name' => 'Pickup Guest',
            'phone' => '0901234567',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);
        $order = FulfillmentOrder::query()->sole();
        $kitchen = $this->user('kitchen');
        $this->actingAs($kitchen)->get(route('kitchen.home'))->assertOk()->assertDontSee($order->order_code);

        $staff = $this->user('staff');
        $this->actingAs($staff)->get(route('pos.fulfillment-orders.index'))->assertOk()->assertSee($order->order_code);
        $this->actingAs($this->user('admin'));
        $this->get(route('admin.fulfillment-orders.index'))->assertOk()->assertSee($order->order_code);
        $this->get(route('admin.fulfillment-orders.show', $order))->assertOk()->assertSee('Pickup Kitchen Item');
        $this->actingAs($staff);
        $this->patch(route('pos.fulfillment-orders.confirm', $order))->assertRedirect();
        $this->assertSame(FulfillmentOrder::STATUS_CONFIRMED, $order->fresh()->status);

        $this->actingAs($kitchen)
            ->get(route('kitchen.home'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Pickup Kitchen Item');
        $ticket = KitchenTicket::query()->where('source_type', 'fulfillment_order')->sole();
        $this->assertSame(KitchenTicket::STATUS_PENDING, $ticket->status);
        $this->post(route('kitchen.tickets.print', $ticket))->assertRedirect(
            route('kitchen.tickets.printable', ['kitchenTicket' => $ticket, 'autoprint' => 1]),
        );
    }

    public function test_bank_transfer_checkout_redirects_to_signed_vietqr_page_and_reports_payment(): void
    {
        $this->enableOrdering();
        foreach (
            [
                SystemSettingCatalog::VIETQR_BANK_ID => 'MB',
                SystemSettingCatalog::VIETQR_ACCOUNT_NUMBER => '1234567890',
                SystemSettingCatalog::VIETQR_ACCOUNT_NAME => 'CONG TY BEER GARDEN',
                SystemSettingCatalog::VIETQR_TRANSFER_PREFIX => 'BG',
            ] as $key => $value
        ) {
            SystemSetting::query()->forceCreate(['key' => $key, 'value' => $value, 'type' => 'string']);
        }
        $product = $this->product('VietQR Item', 45000);
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'pickup']);

        $response = $this->post(route('customer.cart.checkout.store'), [
            'customer_name' => 'Khách VietQR',
            'phone' => '0901234567',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'payment_option' => 'bank_transfer',
        ])->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/cart/payment/', $location);
        $this->get($location)
            ->assertOk()
            ->assertSee('Quét mã để chuyển khoản')
            ->assertSee('90.000 ₫')
            ->assertSee('1234567890');

        $order = FulfillmentOrder::query()->sole();
        $reportUrl = URL::temporarySignedRoute('customer.cart.external-payment.report', now()->addDay(), [
            'fulfillmentOrder' => $order,
        ]);
        $this->post($reportUrl)->assertRedirect(route('customer.cart.checkout.success'));
        $this->assertNotNull($order->fresh()->payment_reported_at);
        $this->assertSame(FulfillmentOrder::PAYMENT_UNPAID, $order->fresh()->payment_status);
    }

    public function test_guest_places_delivery_order_with_server_owned_fee_and_address(): void
    {
        $this->enableOrdering();
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::DELIVERY_FEE,
            'value' => '30000',
            'type' => 'integer',
        ]);
        $product = $this->product('Delivery Snapshot', 45000);
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'delivery']);
        $this->get(route('customer.cart.delivery-checkout'))->assertOk()->assertSee('30.000 ₫')->assertSee('120.000 ₫');

        $this->post(route('customer.cart.delivery-checkout.store'), [
            'customer_name' => 'Delivery Guest',
            'phone' => '0901234567',
            'delivery_address' => '89 Đường Vườn Bia, TP.HCM',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
            'shipping_fee' => 1,
            'total_amount' => 1,
        ])->assertSessionHasErrors(['shipping_fee', 'total_amount']);

        $this->post(route('customer.cart.delivery-checkout.store'), [
            'customer_name' => 'Delivery Guest',
            'phone' => '0901234567',
            'delivery_address' => '89 Đường Vườn Bia, TP.HCM',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('customer.cart.delivery-checkout.success'));

        $order = FulfillmentOrder::with('items')->sole();
        $this->assertSame(FulfillmentOrder::TYPE_DELIVERY, $order->fulfillment_type);
        $this->assertSame(30000, $order->shipping_fee);
        $this->assertSame(120000, $order->total_amount);
        $this->assertSame('89 Đường Vườn Bia, TP.HCM', $order->delivery_address);
        $this->assertSame('Delivery Snapshot', $order->items->sole()->product_name);
        $this->assertNull(session(CustomerCartService::SESSION_KEY));
    }

    public function test_admin_can_edit_pending_external_order_and_server_recalculates_totals(): void
    {
        $this->enableOrdering();
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::DELIVERY_FEE,
            'value' => '30000',
            'type' => 'integer',
        ]);
        $product = $this->product('Món giao chỉnh sửa', 50000);
        $this->post(route('customer.cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('customer.cart.fulfillment.select'), ['fulfillment_type' => 'delivery']);
        $this->post(route('customer.cart.delivery-checkout.store'), [
            'customer_name' => 'Khách cũ',
            'phone' => '0901234567',
            'delivery_address' => 'Địa chỉ cũ',
            'requested_for' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);
        $order = FulfillmentOrder::query()->sole();

        $this->actingAs($this->user('admin'))
            ->putJson(route('admin.fulfillment-orders.update', $order), [
                'customer_name' => 'Khách mới',
                'phone' => '0907654321',
                'email' => 'new@example.test',
                'delivery_address' => '89 Đường Vườn Bia',
                'requested_for' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'note' => 'Gọi trước khi giao',
                'items' => [['product_id' => $product->id, 'quantity' => 3, 'note' => 'Ít cay']],
                'subtotal' => 1,
                'total_amount' => 1,
                'status' => 'confirmed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subtotal', 'total_amount', 'status']);

        $this->putJson(route('admin.fulfillment-orders.update', $order), [
            'customer_name' => 'Khách mới',
            'phone' => '0907654321',
            'email' => 'new@example.test',
            'delivery_address' => '89 Đường Vườn Bia',
            'requested_for' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'note' => 'Gọi trước khi giao',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'note' => 'Ít cay']],
        ])->assertOk();

        $order->refresh();
        $this->assertSame('Khách mới', $order->customer_name);
        $this->assertSame('89 Đường Vườn Bia', $order->delivery_address);
        $this->assertSame(150000, $order->subtotal);
        $this->assertSame(180000, $order->total_amount);
        $this->assertSame(3, $order->items()->sole()->quantity);
        $this->get(route('admin.fulfillment-orders.show', $order))
            ->assertOk()
            ->assertSee('89 Đường Vườn Bia')
            ->assertSee('Chỉnh sửa đơn')
            ->assertSee('Món giao chỉnh sửa');

        $this->patch(route('admin.fulfillment-orders.confirm', $order))->assertRedirect();
        $this->put(route('admin.fulfillment-orders.update', $order), [
            'customer_name' => 'Không được sửa',
            'phone' => '0907654321',
            'delivery_address' => 'Địa chỉ khác',
            'requested_for' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('order');
    }

    private function enableOrdering(): void
    {
        SystemSetting::query()->forceCreate([
            'key' => 'customer_ordering_enabled',
            'value' => 'true',
            'type' => 'boolean',
        ]);
    }

    private function bindGuest(): DiningSession
    {
        $this->enableOrdering();
        $session = $this->diningSession();
        $this->get($this->signedUrl($session))->assertRedirect();

        return $session;
    }

    private function signedUrl(DiningSession $session, $expires = null): string
    {
        return URL::temporarySignedRoute('customer.dining-context.bind', $expires ?? now()->addMinutes(30), [
            'diningSession' => $session->id,
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

    private function diningSession(
        DiningSessionStatus $status = DiningSessionStatus::Active,
        RestaurantTableStatus $tableStatus = RestaurantTableStatus::Occupied,
    ): DiningSession {
        $employee = Employee::query()->forceCreate([
            'employee_code' => 'O-'.fake()->unique()->numberBetween(1, 999999),
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

    private function product(
        string $name = 'Product',
        int $price = 10000,
        string $status = Product::STATUS_ACTIVE,
        bool $available = true,
        string $categoryStatus = Category::STATUS_ACTIVE,
    ): Product {
        $category = Category::query()->forceCreate([
            'name' => 'Category',
            'slug' => 'c-'.fake()->unique()->numberBetween(1, 999999),
            'status' => $categoryStatus,
            'sort_order' => 1,
        ]);

        return Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'p-'.fake()->unique()->numberBetween(1, 999999),
            'price' => $price,
            'status' => $status,
            'is_available' => $available,
        ]);
    }

    private function customerOrder(DiningSession $session, string $name): Order
    {
        $product = $this->product($name);
        $order = Order::query()->forceCreate([
            'order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id,
            'source' => 'customer',
            'ordered_at' => now(),
        ]);
        OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $name,
            'quantity' => 1,
            'unit_price' => $product->price,
            'line_total' => $product->price,
            'status' => 'waiting',
        ]);

        return $order;
    }
}
