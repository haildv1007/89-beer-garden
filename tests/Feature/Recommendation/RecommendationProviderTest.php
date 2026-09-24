<?php

namespace Tests\Feature\Recommendation;

use App\Contracts\RecommendationProvider;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Recommendation\RecommendationInput;
use App\Services\Recommendation\RecommendationService;
use Database\Seeders\DatabaseSeeder;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RecommendationProviderTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config([
            'features.recommendation' => true,
            'recommendation.ai_enabled' => true,
            'recommendation.provider' => 'gemini',
            'recommendation.gemini.api_key' => 'test-secret-key',
            'recommendation.gemini.model' => 'test-model',
            'recommendation.gemini.base_url' => 'https://gemini.example.test',
            'recommendation.gemini.timeout_seconds' => 1,
        ]);
    }

    public function test_valid_ai_output_is_rehydrated_with_current_database_values_and_safe_input(): void
    {
        $category = $this->category();
        $products = [
            $this->product($category, 'First', 21000),
            $this->product($category, 'Second', 32000),
            $this->product($category, 'Third', 43000),
        ];
        $products[0]->forceFill(['price' => 22000])->save();
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();
        Customer::query()->forceCreate([
            'user_id' => $user->id,
            'name' => 'Private Customer',
            'email' => 'private@example.test',
            'phone' => '0900000000',
            'note' => 'Private Address',
        ]);
        Http::fake(['*' => $this->geminiResponse(json_encode([
            'items' => [
                ['product_id' => $products[0]->id, 'quantity' => 2, 'reason' => 'Phù hợp nhóm'],
                ['product_id' => $products[1]->id, 'quantity' => 1, 'reason' => 'Dễ chia sẻ'],
            ],
            'summary' => 'Set cân bằng cho cả nhóm.',
        ], JSON_THROW_ON_ERROR))]);

        $result = $this->recommend(user: $user, partySize: 3, note: 'Không quá cay');

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Set cân bằng cho cả nhóm.', $result['summary']);
        $this->assertSame(22000, $result['items'][0]['price']);
        $this->assertSame(76000, $result['estimated_total']);
        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return $request->hasHeader('x-goog-api-key', 'test-secret-key')
                && isset($payload['systemInstruction'], $payload['generationConfig']['responseJsonSchema'])
                && ! str_contains($encoded, 'customer_id')
                && ! str_contains($encoded, 'email')
                && ! str_contains($encoded, 'phone')
                && ! str_contains($encoded, 'address')
                && ! str_contains($encoded, 'Private Customer')
                && ! str_contains($encoded, 'private@example.test')
                && ! str_contains($encoded, '0900000000')
                && ! str_contains($encoded, 'Private Address');
        });
    }

    public function test_disabled_ai_and_missing_key_do_not_make_external_requests(): void
    {
        $this->seedCatalog();
        Http::fake();
        config(['recommendation.ai_enabled' => false]);

        $this->assertNotSame('ai', $this->recommend()['source']);
        Http::assertNothingSent();

        config([
            'recommendation.ai_enabled' => true,
            'recommendation.gemini.api_key' => null,
        ]);
        $this->app->forgetInstance(RecommendationProvider::class);

        $this->assertNotSame('ai', $this->recommend()['source']);
        Http::assertNothingSent();
    }

    public function test_provider_failures_and_invalid_outputs_fall_back_with_safe_logs(): void
    {
        $products = $this->seedCatalog();
        Log::spy();
        $responses = [
            Http::response(['error' => ['message' => 'secret provider detail']], 500),
            $this->geminiResponse('{invalid'),
            $this->geminiResponse(json_encode([
                'items' => [['product_id' => 999999, 'quantity' => 1, 'reason' => 'Fake']],
                'summary' => 'Invalid',
            ], JSON_THROW_ON_ERROR)),
            $this->geminiResponse(json_encode([
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 1, 'reason' => 'One'],
                    ['product_id' => $products[0]->id, 'quantity' => 1, 'reason' => 'Duplicate'],
                ],
                'summary' => 'Invalid',
            ], JSON_THROW_ON_ERROR)),
            $this->geminiResponse(json_encode([
                'items' => [['product_id' => $products[0]->id, 'quantity' => 0, 'reason' => 'Invalid']],
                'summary' => 'Invalid',
            ], JSON_THROW_ON_ERROR)),
            $this->geminiResponse(json_encode([
                'items' => [[
                    'product_id' => $products[0]->id,
                    'quantity' => 1,
                    'reason' => 'Invalid',
                    'price' => 1,
                ]],
                'summary' => 'Invalid',
            ], JSON_THROW_ON_ERROR)),
        ];

        foreach ($responses as $fakeResponse) {
            Http::fake(['*' => $fakeResponse]);
            $result = $this->recommend(note: 'private note');
            $this->assertNotSame('ai', $result['source']);
            $this->assertStringNotContainsString('secret provider detail', $result['summary']);
        }

        Log::shouldHaveReceived('warning')
            ->times(6)
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context, JSON_THROW_ON_ERROR);

                return $message === 'Recommendation provider fallback.'
                    && ! str_contains($encoded, 'test-secret-key')
                    && ! str_contains($encoded, 'private note');
            });
    }

    public function test_provider_timeout_falls_back_without_retrying(): void
    {
        $this->seedCatalog();
        Http::fake(['*' => Http::failedConnection('Operation timed out')]);

        $this->assertNotSame('ai', $this->recommend()['source']);

        Http::assertSentCount(1);
    }

    public function test_product_revalidation_failure_discards_ai_result_and_falls_back(): void
    {
        $products = $this->seedCatalog();
        Http::fake(function () use ($products) {
            $products[0]->forceFill(['is_available' => false])->save();

            return $this->geminiResponse(json_encode([
                'items' => [['product_id' => $products[0]->id, 'quantity' => 1, 'reason' => 'Changed']],
                'summary' => 'Must be discarded',
            ], JSON_THROW_ON_ERROR));
        });

        $result = $this->recommend();

        $this->assertNotSame('ai', $result['source']);
        $this->assertNotContains($products[0]->id, collect($result['items'])->pluck('product_id')->all());
    }

    /** @return array<string, mixed> */
    private function recommend(
        ?User $user = null,
        int $partySize = 2,
        ?string $note = null,
    ): array {
        return app(RecommendationService::class)->recommend(
            new RecommendationInput($partySize, null, [], $note),
            $user,
        )->toArray();
    }

    /** @return list<Product> */
    private function seedCatalog(): array
    {
        $category = $this->category();

        return [
            $this->product($category, 'First', 20000),
            $this->product($category, 'Second', 30000),
            $this->product($category, 'Third', 40000),
        ];
    }

    private function category(): Category
    {
        return Category::query()->forceCreate([
            'name' => 'Food',
            'slug' => 'provider-food-'.++$this->sequence,
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => $this->sequence,
        ]);
    }

    private function product(Category $category, string $name, int $price): Product
    {
        return Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'provider-product-'.++$this->sequence,
            'short_description' => 'Description',
            'price' => $price,
            'status' => Product::STATUS_ACTIVE,
            'is_available' => true,
        ]);
    }

    private function geminiResponse(string $content): PromiseInterface|Response
    {
        return Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => $content]],
                ],
            ]],
        ]);
    }
}
