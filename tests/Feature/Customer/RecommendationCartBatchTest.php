<?php

namespace Tests\Feature\Customer;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerOrder\CustomerCartService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationCartBatchTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_atomically_add_a_set_and_existing_notes_are_preserved(): void
    {
        $category = $this->category();
        $first = $this->product($category, 'First', 20000);
        $second = $this->product($category, 'Second', 30000);
        $existing = [
            (string) $first->id => [
                'product_id' => $first->id,
                'quantity' => 2,
                'note' => 'No ice',
            ],
        ];

        $response = $this->withSession([CustomerCartService::SESSION_KEY => $existing])
            ->postJson(route('customer.cart.items.batch-store'), [
                'items' => [
                    ['product_id' => $first->id, 'quantity' => 1],
                    ['product_id' => $second->id, 'quantity' => 2],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('cart_count', 5)
            ->assertJsonPath('subtotal', 120000)
            ->assertJsonPath('message', __('recommendation.cart.added'));
        $this->assertSame([
            (string) $first->id => ['product_id' => $first->id, 'quantity' => 3, 'note' => 'No ice'],
            (string) $second->id => ['product_id' => $second->id, 'quantity' => 2, 'note' => null],
        ], session(CustomerCartService::SESSION_KEY));
    }

    public function test_authenticated_customer_can_add_a_set(): void
    {
        $product = $this->product($this->category(), 'Authenticated', 25000);
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();

        $this->actingAs($user)
            ->postJson(route('customer.cart.items.batch-store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 2]],
            ])
            ->assertOk()
            ->assertJsonPath('cart_count', 2);
    }

    public function test_invalid_product_makes_the_whole_batch_fail_without_changing_cart(): void
    {
        $category = $this->category();
        $valid = $this->product($category, 'Valid', 20000);
        $unavailable = $this->product($category, 'Unavailable', 20000, available: false);
        $existing = [
            (string) $valid->id => ['product_id' => $valid->id, 'quantity' => 2, 'note' => null],
        ];

        $this->withSession([CustomerCartService::SESSION_KEY => $existing])
            ->postJson(route('customer.cart.items.batch-store'), [
                'items' => [
                    ['product_id' => $valid->id, 'quantity' => 1],
                    ['product_id' => $unavailable->id, 'quantity' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertSame($existing, session(CustomerCartService::SESSION_KEY));
    }

    public function test_every_product_and_category_eligibility_failure_keeps_the_cart_unchanged(): void
    {
        $category = $this->category();
        $valid = $this->product($category, 'Valid', 20000);
        $inactive = $this->product($category, 'Inactive', 20000, Product::STATUS_INACTIVE);
        $deleted = $this->product($category, 'Deleted', 20000);
        $deleted->delete();
        $inactiveCategory = $this->category();
        $inactiveCategory->forceFill(['status' => Category::STATUS_INACTIVE])->save();
        $inInactiveCategory = $this->product($inactiveCategory, 'Inactive category', 20000);
        $deletedCategory = $this->category();
        $inDeletedCategory = $this->product($deletedCategory, 'Deleted category', 20000);
        $deletedCategory->delete();
        $existing = [
            (string) $valid->id => ['product_id' => $valid->id, 'quantity' => 2, 'note' => null],
        ];

        foreach ([999999, $inactive->id, $deleted->id, $inInactiveCategory->id, $inDeletedCategory->id] as $invalidId) {
            $this->withSession([CustomerCartService::SESSION_KEY => $existing])
                ->postJson(route('customer.cart.items.batch-store'), [
                    'items' => [
                        ['product_id' => $valid->id, 'quantity' => 1],
                        ['product_id' => $invalidId, 'quantity' => 1],
                    ],
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('items');
            $this->assertSame($existing, session(CustomerCartService::SESSION_KEY));
        }
    }

    public function test_batch_rejects_duplicates_controlled_fields_and_cumulative_overflow(): void
    {
        $product = $this->product($this->category(), 'Product', 20000);

        $this->postJson(route('customer.cart.items.batch-store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 1],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'estimated_total' => 1,
            'source' => 'ai',
        ])->assertJsonValidationErrors([
            'items.0.price',
            'items.1.product_id',
            'estimated_total',
            'source',
        ]);

        $existing = [
            (string) $product->id => ['product_id' => $product->id, 'quantity' => 980, 'note' => null],
        ];
        $this->withSession([CustomerCartService::SESSION_KEY => $existing])
            ->postJson(route('customer.cart.items.batch-store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 21]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->assertSame($existing, session(CustomerCartService::SESSION_KEY));
    }

    public function test_batch_route_uses_session_blocking(): void
    {
        $route = app('router')->getRoutes()->getByName('customer.cart.items.batch-store');

        $this->assertNotNull($route);
        $this->assertSame(5, $route->locksFor());
        $this->assertSame(5, $route->waitsFor());
    }

    private function category(): Category
    {
        return Category::query()->forceCreate([
            'name' => 'Food '.++$this->sequence,
            'slug' => 'food-'.$this->sequence,
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
            'slug' => 'batch-product-'.++$this->sequence,
            'short_description' => 'Description',
            'price' => $price,
            'status' => $status,
            'is_available' => $available,
        ]);
    }
}
