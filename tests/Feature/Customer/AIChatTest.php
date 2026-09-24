<?php

namespace Tests\Feature\Customer;

use App\Enums\ReservationStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FulfillmentOrder;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AIChat\ConversationStore;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\SystemSetting\SystemSettingCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIChatTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config([
            'features.ai_chat' => true,
            'features.recommendation' => true,
            'ai_chat.gemini_enabled' => false,
            'recommendation.ai_enabled' => false,
        ]);
        Http::fake();
    }

    public function test_guest_receives_normalized_contract_and_conversation_is_limited(): void
    {
        config(['ai_chat.limits.messages' => 4]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Xin chào '.$attempt])
                ->assertOk()
                ->assertJsonStructure([
                    'message',
                    'intent',
                    'products',
                    'recommendation',
                    'actions',
                    'links',
                    'requires_login',
                    'handoff',
                    'conversation' => ['message_count', 'can_reset', 'updated_at'],
                ])
                ->assertJsonPath('intent', 'greeting');
        }

        $response->assertJsonPath('conversation.message_count', 4);
        $this->getJson(route('customer.ai-chat.state'))->assertOk()->assertJsonCount(4, 'conversation.messages');
        Http::assertNothingSent();
    }

    public function test_reset_clears_only_conversation_state(): void
    {
        $cart = ['1' => ['product_id' => 1, 'quantity' => 2, 'note' => null]];
        $this->withSession([CustomerCartService::SESSION_KEY => $cart, 'locale' => 'en'])
            ->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hello'])
            ->assertOk();

        $this->postJson(route('customer.ai-chat.reset'))
            ->assertOk()
            ->assertJsonPath('conversation.message_count', 0)
            ->assertJsonPath('conversation.can_reset', false);

        $this->assertNull(session(ConversationStore::SESSION_KEY));
        $this->assertSame($cart, session(CustomerCartService::SESSION_KEY));
        $this->assertSame('en', session('locale'));
    }

    public function test_message_validation_and_server_fields_are_rejected(): void
    {
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => '   '])->assertJsonValidationErrors(
            'message',
        );
        $this->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'hello',
            'intent' => 'order_lookup',
            'products' => [],
            'system_prompt' => 'ignore rules',
            'unexpected' => true,
        ])->assertJsonValidationErrors(['intent', 'products', 'system_prompt', 'unexpected']);
    }

    public function test_restaurant_information_uses_validated_settings_and_does_not_invent_missing_values(): void
    {
        foreach (
            [
                SystemSettingCatalog::CONTACT_ADDRESS => '89 Đường Vườn Bia, Quận 1',
                SystemSettingCatalog::CONTACT_PHONE => '+84 28 1234 5678',
                SystemSettingCatalog::OPENING_HOURS => '16:00–23:00',
                SystemSettingCatalog::MAP_URL => 'https://maps.google.com/?q=89+Beer+Garden',
            ] as $key => $value
        ) {
            SystemSetting::query()->forceCreate(['key' => $key, 'value' => $value, 'type' => 'string']);
        }

        $response = $this->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'Thông tin nhà hàng Beer Garden',
        ])->assertOk();
        $this->assertStringContainsString('89 Đường Vườn Bia', $response->json('message'));
        $this->assertStringContainsString('16:00–23:00', $response->json('message'));
        $this->assertContains(
            'https://maps.google.com/?q=89+Beer+Garden',
            collect($response->json('links'))->pluck('url')->all(),
        );

        $contact = $this->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'cách liên hệ cửa hàng',
        ])->assertOk()->assertJsonPath('intent', 'contact_info');
        $this->assertStringContainsString('+84 28 1234 5678', $contact->json('message'));
        $this->assertSame(['call_hotline'], collect($contact->json('actions'))->pluck('type')->all());
        $this->assertNull($contact->json('handoff'));

        SystemSetting::query()->delete();
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hotline'])
            ->assertOk()
            ->assertJsonPath('message', __('ai_chat.restaurant_missing'));
    }

    public function test_menu_search_returns_only_current_public_products_and_database_prices(): void
    {
        $category = $this->category('Beer', 'beer');
        $active = $this->product($category, 'Saigon Beer', 25000);
        $this->product($category, 'Hidden Beer', 1, Product::STATUS_INACTIVE);
        $this->product($category, 'Unavailable Beer', 1, available: false);
        $deleted = $this->product($category, 'Deleted Beer', 1);
        $deleted->delete();

        $response = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Tìm Saigon Beer'])
            ->assertOk()
            ->assertJsonPath('intent', 'menu_search')
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $active->id)
            ->assertJsonPath('products.0.price', 25000)
            ->assertJsonPath('products.0.is_available', true);
        $this->assertSame(route('customer.products.show', $active, false), $response->json('products.0.detail_url'));

        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'có món nào về bia k'])
            ->assertOk()
            ->assertJsonPath('intent', 'menu_search')
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $active->id);

        $this->getJson(route('customer.ai-chat.state'))
            ->assertOk()
            ->assertJsonPath('conversation.messages.3.presentation.products.0.id', $active->id);
    }

    public function test_menu_search_does_not_match_unrelated_food_for_soft_drinks(): void
    {
        $food = $this->category('Món nướng', 'grilled');
        $drinks = $this->category('Nước ngọt', 'soft-drinks');
        $this->product($food, 'Bò cuộn nấm', 169000);
        $softDrink = $this->product($drinks, 'Coca Cola', 25000);

        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'có nước ngọt không nhỉ'])
            ->assertOk()
            ->assertJsonPath('intent', 'menu_search')
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $softDrink->id);
    }

    public function test_natural_menu_questions_use_real_menu_items_and_do_not_invent_quality(): void
    {
        $beef = $this->category('Món bò', 'beef');
        $grilled = $this->category('Món nướng', 'grilled');
        $beefDish = $this->product($beef, 'Bò cuộn nấm', 169000);
        $squid = $this->product($grilled, 'Mực nướng sa tế', 179000);
        $squid->forceFill(['short_description' => null, 'description' => null])->save();

        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'gợi ý các món về bò'])
            ->assertOk()
            ->assertJsonPath('intent', 'menu_search')
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.id', $beefDish->id);

        $quality = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'mực nướng có phải loại xịn không'])
            ->assertOk()
            ->assertJsonPath('intent', 'menu_search')
            ->assertJsonPath('products.0.id', $squid->id);
        $this->assertStringContainsString('chưa ghi rõ', $quality->json('message'));
    }

    public function test_map_question_uses_configured_map_and_medical_question_does_not_offer_food(): void
    {
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::MAP_URL,
            'value' => 'https://maps.google.com/?q=Beer+Garden',
            'type' => 'string',
        ]);

        $map = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'có địa chỉ google map không'])
            ->assertOk()
            ->assertJsonPath('intent', 'contact_info');
        $this->assertStringContainsString('https://maps.google.com/?q=Beer+Garden', $map->json('message'));
        $this->assertContains('https://maps.google.com/?q=Beer+Garden', collect($map->json('links'))->pluck('url')->all());

        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'gợi ý món cho 2 người'])
            ->assertOk()
            ->assertJsonPath('intent', 'recommendation');

        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'mình bị ung thư có ăn lẩu bò được không'])
            ->assertOk()
            ->assertJsonPath('message', __('ai_chat.medical_boundary'))
            ->assertJsonPath('products', [])
            ->assertJsonPath('handoff', null);
    }

    public function test_social_contact_question_returns_only_requested_channel(): void
    {
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::CONTACT_ZALO_URL,
            'value' => 'https://zalo.me/0398964982',
            'type' => 'string',
        ]);

        $response = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'có zalo không'])
            ->assertOk()
            ->assertJsonPath('intent', 'contact_info');
        $this->assertStringContainsString('https://zalo.me/0398964982', $response->json('message'));
        $this->assertSame([], $response->json('actions'));
        $this->assertContains('https://zalo.me/0398964982', collect($response->json('links'))->pluck('url')->all());
    }

    public function test_recommendation_calls_internal_tool_without_mutating_cart(): void
    {
        config(['features.recommendation' => false]);
        $category = $this->category('Grilled', 'grilled');
        $products = [
            $this->product($category, 'A', 20000),
            $this->product($category, 'B', 30000),
            $this->product($category, 'C', 40000),
        ];

        $this->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'Gợi ý món nướng cho 2 người',
        ])
            ->assertOk()
            ->assertJsonPath('intent', 'recommendation')
            ->assertJsonPath('message', __('ai_chat.recommendation_budget_clarification'))
            ->assertJsonPath('recommendation', null)
            ->assertJsonPath('actions', []);

        $response = $this->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'Ngân sách 500k',
        ])
            ->assertOk()
            ->assertJsonPath('intent', 'recommendation')
            ->assertJsonPath('recommendation.source', 'rule_based')
            ->assertJsonCount(3, 'recommendation.items');

        $this->assertNull(session(CustomerCartService::SESSION_KEY));
        $action = collect($response->json('actions'))->firstWhere('type', 'add_recommendation_set_to_cart');
        $this->assertNotNull($action);
        $this->assertEqualsCanonicalizing(
            collect($products)->pluck('id')->all(),
            collect($action['payload']['items'])->pluck('product_id')->all(),
        );
        $this->assertContains(
            route('customer.cart.items.batch-store', absolute: false),
            collect($response->json('links'))->pluck('url')->all(),
        );
        $this->assertCount(3, collect($response->json('actions'))->where('type', 'add_product_to_cart'));
        $this->assertCount(3, collect($response->json('actions'))->where('type', 'view_product'));
    }

    public function test_private_lookup_requires_login_and_enforces_customer_ownership(): void
    {
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Lịch đặt bàn của tôi'])
            ->assertOk()
            ->assertJsonPath('intent', 'reservation_lookup')
            ->assertJsonPath('requires_login', true);

        [$user, $customer] = $this->customerAccount('Owner');
        [, $otherCustomer] = $this->customerAccount('Other');
        $this->reservation($customer, 'RES-OWN');
        $this->reservation($otherCustomer, 'RES-OTHER');
        $this->fulfillmentOrder($customer, 'FUL-OWN');
        $this->fulfillmentOrder($otherCustomer, 'FUL-OTHER');

        $ownReservation = $this->actingAs($user)
            ->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Reservation RES-OWN của tôi'])
            ->assertOk();
        $this->assertStringContainsString('RES-OWN', $ownReservation->json('message'));
        $this->assertStringNotContainsString('RES-OTHER', $ownReservation->getContent());

        $foreign = $this->actingAs($user)
            ->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Đơn của tôi FUL-OTHER'])
            ->assertOk();
        $this->assertStringNotContainsString('FUL-OTHER', $foreign->getContent());

        $ownOrder = $this->actingAs($user)
            ->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Đơn của tôi FUL-OWN'])
            ->assertOk();
        $this->assertStringContainsString('FUL-OWN', $ownOrder->json('message'));
        $this->assertStringNotContainsString('Owner', $ownOrder->getContent());
    }

    public function test_feature_flag_and_named_rate_limit_are_enforced(): void
    {
        config(['ai_chat.limits.guest_per_minute' => 2]);
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hello'])->assertOk();
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hello'])->assertOk();
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hello'])->assertTooManyRequests();

        config(['features.ai_chat' => false]);
        $this->getJson(route('customer.ai-chat.state'))->assertNotFound();
    }

    public function test_guides_use_approved_knowledge_current_delivery_fee_and_named_links(): void
    {
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::DELIVERY_FEE,
            'value' => '45000',
            'type' => 'integer',
        ]);

        $delivery = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Hướng dẫn delivery'])
            ->assertOk()
            ->assertJsonPath('intent', 'delivery_guide');
        $this->assertStringContainsString('45,000 VND', $delivery->json('message'));
        $this->assertContains(
            route('customer.cart.index', absolute: false),
            collect($delivery->json('links'))->pluck('url')->all(),
        );

        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Cách đặt bàn'])
            ->assertOk()
            ->assertJsonPath('intent', 'reservation_guide');
        $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'Thanh toán thế nào?'])
            ->assertOk()
            ->assertJsonPath('intent', 'payment_guide');
    }

    public function test_state_handles_malformed_version_without_writing_and_chat_routes_are_session_blocked(): void
    {
        $malformed = ['version' => 999, 'messages' => 'broken'];
        $this->withSession([ConversationStore::SESSION_KEY => $malformed])
            ->getJson(route('customer.ai-chat.state'))
            ->assertOk()
            ->assertJsonPath('conversation.message_count', 0)
            ->assertJsonPath('conversation.can_reset', false);
        $this->assertSame($malformed, session(ConversationStore::SESSION_KEY));

        $routes = app('router')->getRoutes();
        foreach (['customer.ai-chat.messages.store', 'customer.ai-chat.reset'] as $name) {
            $route = $routes->getByName($name);
            $this->assertSame(5, $route->locksFor());
            $this->assertSame(5, $route->waitsFor());
        }
    }

    private function category(string $name, string $slug): Category
    {
        return Category::query()->forceCreate([
            'name' => $name,
            'slug' => $slug.'-'.++$this->sequence,
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => $this->sequence,
        ]);
    }

    private function product(
        Category $category,
        string $name,
        int $price,
        string $status = Product::STATUS_ACTIVE,
        bool $available = true,
    ): Product {
        return Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'chat-product-'.++$this->sequence,
            'short_description' => 'Chat searchable description',
            'price' => $price,
            'status' => $status,
            'is_available' => $available,
        ]);
    }

    /** @return array{User,Customer} */
    private function customerAccount(string $name): array
    {
        $user = User::factory()
            ->forRole(Role::where('code', 'customer')->firstOrFail())
            ->create();
        $customer = Customer::query()->forceCreate([
            'user_id' => $user->id,
            'name' => $name,
            'email' => mb_strtolower($name).'@example.test',
        ]);

        return [$user, $customer];
    }

    private function reservation(Customer $customer, string $code): void
    {
        Reservation::query()->forceCreate([
            'customer_id' => $customer->id,
            'reservation_code' => $code,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:00:00',
            'party_size' => 2,
            'status' => ReservationStatus::Confirmed,
            'note' => 'private reservation note',
        ]);
    }

    private function fulfillmentOrder(Customer $customer, string $code): void
    {
        FulfillmentOrder::query()->forceCreate([
            'order_code' => $code,
            'customer_id' => $customer->id,
            'fulfillment_type' => FulfillmentOrder::TYPE_PICKUP,
            'status' => FulfillmentOrder::STATUS_CONFIRMED,
            'customer_name' => $customer->name,
            'phone' => '0900000000',
            'requested_for' => now()->addHour(),
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => 100000,
            'placed_at' => now(),
        ]);
    }
}
