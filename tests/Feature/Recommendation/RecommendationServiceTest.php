<?php

namespace Tests\Feature\Recommendation;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use App\Services\Recommendation\RecommendationInput;
use App\Services\Recommendation\RecommendationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config([
            'features.recommendation' => true,
            'recommendation.ai_enabled' => false,
        ]);
    }

    public function test_internal_tool_returns_only_eligible_products_with_server_owned_totals(): void
    {
        $food = $this->category('Food', 'food');
        $inactiveCategory = $this->category('Hidden', 'hidden', Category::STATUS_INACTIVE);
        $eligible = [
            $this->product($food, 'Food A', 30000),
            $this->product($food, 'Food B', 40000),
            $this->product($food, 'Food C', 50000),
        ];
        $this->product($food, 'Inactive', 1, Product::STATUS_INACTIVE);
        $this->product($food, 'Unavailable', 1, available: false);
        $this->product($inactiveCategory, 'Hidden category', 1);
        $deleted = $this->product($food, 'Deleted', 1);
        $deleted->delete();

        $result = $this->recommend(new RecommendationInput(2, null, [], null));

        $this->assertCount(3, $result['items']);
        $this->assertSame(120000, $result['estimated_total']);
        $this->assertNull($result['budget']);
        $this->assertSame('not_provided', $result['budget_status']);
        $this->assertSame('popular', $result['source']);
        $ids = collect($result['items'])->pluck('product_id')->all();
        $this->assertEqualsCanonicalizing(collect($eligible)->pluck('id')->all(), $ids);
        foreach ($result['items'] as $item) {
            $this->assertTrue($item['availability']);
            $this->assertSame($item['price'] * $item['quantity'], $item['line_total']);
        }
    }

    public function test_budget_status_and_totals_use_current_database_prices(): void
    {
        $category = $this->category('Food', 'food');
        $first = $this->product($category, 'A', 10000);
        $this->product($category, 'B', 12000);
        $this->product($category, 'C', 15000);
        $this->product($category, 'D', 90000);
        $first->forceFill(['price' => 11000])->save();

        $within = $this->recommend(new RecommendationInput(1, 50000, [], null));
        $over = $this->recommend(new RecommendationInput(1, 10000, [], null));

        $this->assertSame(38000, $within['estimated_total']);
        $this->assertSame('within_budget', $within['budget_status']);
        $this->assertSame(38000, $over['estimated_total']);
        $this->assertSame('over_budget', $over['budget_status']);
    }

    public function test_rule_based_result_is_deterministic_and_uses_cart_snapshot(): void
    {
        $category = $this->category('Grilled', 'grilled');
        $inCart = $this->product($category, 'In cart', 20000);
        $this->product($category, 'B', 20000);
        $this->product($category, 'C', 20000);
        $this->product($category, 'D', 20000);
        $input = new RecommendationInput(2, null, ['grilled'], null);
        $cartItems = [['product_id' => $inCart->id, 'quantity' => 1]];

        $first = $this->recommend($input, cartItems: $cartItems);
        $second = $this->recommend($input, cartItems: $cartItems);

        $this->assertSame($first, $second);
        $this->assertNotContains($inCart->id, collect($first['items'])->pluck('product_id')->all());
        $this->assertSame('rule_based', $first['source']);
    }

    public function test_large_party_respects_item_and_quantity_limits(): void
    {
        for ($index = 1; $index <= 8; $index++) {
            $category = $this->category('Category '.$index, 'category-'.$index);
            $this->product($category, 'Product '.$index, 10000 + $index);
        }

        $result = $this->recommend(new RecommendationInput(50, null, [], null));

        $this->assertCount(6, $result['items']);
        foreach ($result['items'] as $item) {
            $this->assertGreaterThanOrEqual(1, $item['quantity']);
            $this->assertLessThanOrEqual(50, $item['quantity']);
        }
    }

    public function test_popularity_uses_only_completed_paid_non_cancelled_recent_items(): void
    {
        $category = $this->category('Food', 'food');
        $eligible = $this->product($category, 'Eligible popular', 20000);
        $cancelled = $this->product($category, 'Cancelled', 20000);
        $unpaid = $this->product($category, 'Unpaid', 20000);
        $old = $this->product($category, 'Old', 20000);
        $this->product($category, 'No history', 20000);
        $this->transaction($eligible, 5);
        $this->transaction($cancelled, 100, OrderItemStatus::Cancelled);
        $this->transaction($unpaid, 100, billStatus: BillStatus::Unpaid);
        $this->transaction($old, 100, daysAgo: 91);

        $result = $this->recommend(new RecommendationInput(1, null, [], null));

        $this->assertSame('popular', $result['source']);
        $this->assertSame($eligible->id, $result['items'][0]['product_id']);
    }

    public function test_customer_history_is_optional_and_passed_without_http_request_coupling(): void
    {
        $category = $this->category('Food', 'food');
        $this->product($category, 'First', 20000);
        $this->product($category, 'Second', 20000);
        $this->product($category, 'Third', 20000);
        $historyProduct = $this->product($category, 'History', 20000);
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();
        $customer = Customer::query()->forceCreate([
            'user_id' => $user->id,
            'name' => 'Private Name',
            'email' => 'private@example.test',
            'phone' => '0900000000',
        ]);
        $this->transaction($historyProduct, 3, customer: $customer);

        $result = $this->recommend(new RecommendationInput(1, null, [], null), $user);

        $this->assertSame($historyProduct->id, $result['items'][0]['product_id']);
        $this->assertSame(__('recommendation.reasons.history'), $result['items'][0]['reason']);
        $this->assertStringNotContainsString('Private Name', json_encode($result, JSON_THROW_ON_ERROR));

        $userWithoutProfile = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();
        $this->assertNotEmpty(
            $this->recommend(new RecommendationInput(1, null, [], null), $userWithoutProfile)['items'],
        );
    }

    public function test_empty_catalog_returns_safe_popular_result(): void
    {
        $result = $this->recommend(new RecommendationInput(2, 10000, [], null));

        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['estimated_total']);
        $this->assertSame('within_budget', $result['budget_status']);
        $this->assertSame('popular', $result['source']);
        $this->assertSame(__('recommendation.summary.empty'), $result['summary']);
    }

    /**
     * @param  list<array{product_id:int, quantity:int}>  $cartItems
     * @return array<string, mixed>
     */
    private function recommend(
        RecommendationInput $input,
        ?User $user = null,
        array $cartItems = [],
    ): array {
        return app(RecommendationService::class)->recommend($input, $user, $cartItems)->toArray();
    }

    private function category(string $name, string $slug, string $status = Category::STATUS_ACTIVE): Category
    {
        return Category::query()->forceCreate([
            'name' => $name,
            'slug' => $slug.'-'.++$this->sequence,
            'status' => $status,
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
            'slug' => 'recommendation-product-'.++$this->sequence,
            'short_description' => 'Short description',
            'price' => $price,
            'status' => $status,
            'is_available' => $available,
        ]);
    }

    private function transaction(
        Product $product,
        int $quantity,
        OrderItemStatus $itemStatus = OrderItemStatus::Served,
        BillStatus $billStatus = BillStatus::Paid,
        int $daysAgo = 0,
        ?Customer $customer = null,
    ): void {
        $number = ++$this->sequence;
        $employee = Employee::query()->forceCreate([
            'employee_code' => 'REC-'.$number,
            'name' => 'Recommendation fixture',
            'status' => 'active',
        ]);
        $table = RestaurantTable::query()->forceCreate([
            'code' => 'REC-T-'.$number,
            'name' => 'Recommendation table',
            'capacity' => 4,
            'runtime_status' => 'available',
            'is_active' => true,
        ]);
        $time = now()->subDays($daysAgo);
        $session = DiningSession::query()->forceCreate([
            'session_code' => 'REC-DS-'.$number,
            'table_id' => $table->id,
            'customer_id' => $customer?->id,
            'opened_by_employee_id' => $employee->id,
            'completed_by_employee_id' => $employee->id,
            'status' => DiningSessionStatus::Completed,
            'started_at' => $time->copy()->subHour(),
            'ended_at' => $time,
            'guest_count' => 2,
        ]);
        $order = Order::query()->forceCreate([
            'order_code' => 'REC-O-'.$number,
            'dining_session_id' => $session->id,
            'source' => 'staff',
            'ordered_at' => $time,
        ]);
        OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'line_total' => $product->price * $quantity,
            'status' => $itemStatus,
        ]);
        Bill::query()->forceCreate([
            'bill_code' => 'REC-B-'.$number,
            'dining_session_id' => $session->id,
            'subtotal' => $product->price * $quantity,
            'discount_amount' => 0,
            'total_amount' => $product->price * $quantity,
            'status' => $billStatus,
            'issued_at' => $time,
        ]);
    }
}
