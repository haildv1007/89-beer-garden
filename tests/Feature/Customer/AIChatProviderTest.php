<?php

namespace Tests\Feature\Customer;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AIChatProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config([
            'features.ai_chat' => true,
            'ai_chat.gemini_enabled' => true,
            'ai_chat.provider' => 'gemini',
            'recommendation.gemini.api_key' => 'chat-test-secret',
            'recommendation.gemini.model' => 'test-model',
            'recommendation.gemini.base_url' => 'https://gemini.example.test',
        ]);
    }

    public function test_valid_structured_output_uses_backend_product_and_links_without_sending_customer_pii(): void
    {
        $category = Category::query()->forceCreate([
            'name' => 'Beer', 'slug' => 'beer', 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1,
        ]);
        $product = Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => 'Safe Beer',
            'slug' => 'safe-beer',
            'price' => 35000,
            'status' => Product::STATUS_ACTIVE,
            'is_available' => true,
        ]);
        $user = User::factory()->forRole(Role::where('code', 'customer')->firstOrFail())->create();
        Customer::query()->forceCreate([
            'user_id' => $user->id,
            'name' => 'Private Name',
            'email' => 'owner@example.test',
            'phone' => '0900000000',
        ]);
        Http::fake(['*' => $this->providerResponse([
            'intent' => 'menu_search',
            'reply' => 'Provider reply',
            'extracted_context' => [],
            'requested_tool' => 'menu_search',
            'product_ids' => [$product->id],
            'suggested_action_types' => ['view_product'],
            'url' => 'https://evil.example.test',
        ])]);

        $response = $this->actingAs($user)->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'Tìm bia, email của tôi là leak@example.test và số 0912345678',
        ])->assertOk()
            ->assertJsonPath('intent', 'menu_search')
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.price', 35000);

        $this->assertStringNotContainsString('evil.example.test', $response->getContent());
        Http::assertSent(function (Request $request): bool {
            $body = $request->body();
            $schema = $request->data()['generationConfig']['responseJsonSchema']['properties'] ?? [];
            $budget = $schema['extracted_context']['properties']['budget'] ?? [];
            $requestedTool = $schema['requested_tool'] ?? [];

            return $request->hasHeader('x-goog-api-key', 'chat-test-secret')
                && ($budget['type'] ?? null) === 'integer'
                && ! array_key_exists('nullable', $budget)
                && ($requestedTool['type'] ?? null) === 'string'
                && ! array_key_exists('nullable', $requestedTool)
                && str_contains($body, 'Safe Beer')
                && ! str_contains($body, 'Private Name')
                && ! str_contains($body, 'owner@example.test')
                && ! str_contains($body, 'leak@example.test')
                && ! str_contains($body, '0912345678');
        });
    }

    public function test_unknown_intent_tool_or_action_is_rejected_and_uses_deterministic_fallback(): void
    {
        foreach ([
            ['intent' => 'delete_order', 'requested_tool' => 'handoff', 'suggested_action_types' => []],
            ['intent' => 'greeting', 'requested_tool' => 'delete_order', 'suggested_action_types' => []],
            ['intent' => 'greeting', 'requested_tool' => 'handoff', 'suggested_action_types' => ['delete_order']],
        ] as $override) {
            Http::fake(['*' => $this->providerResponse(array_replace([
                'intent' => 'greeting',
                'reply' => '<b>Unsafe</b>',
                'extracted_context' => [],
                'requested_tool' => 'handoff',
                'product_ids' => [],
                'suggested_action_types' => [],
            ], $override))]);

            $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hello'])
                ->assertOk()
                ->assertJsonPath('intent', 'greeting')
                ->assertJsonPath('message', __('ai_chat.greeting'));
        }
    }

    public function test_timeout_falls_back_once_and_safe_log_excludes_message_and_secret(): void
    {
        Log::spy();
        Http::fake(['*' => Http::failedConnection('Operation timed out')]);

        $this->postJson(route('customer.ai-chat.messages.store'), [
            'message' => 'hello private-message-value',
        ])->assertOk()->assertJsonPath('intent', 'greeting');

        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
            $encoded = json_encode($context, JSON_THROW_ON_ERROR);

            return $message === 'AI chat provider fallback.'
                && ! str_contains($encoded, 'private-message-value')
                && ! str_contains($encoded, 'chat-test-secret');
        });
    }

    public function test_invalid_json_falls_back_without_exposing_provider_content(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [[
            'content' => ['parts' => [['text' => '{private-invalid-json']]],
        ]]])]);

        $response = $this->postJson(route('customer.ai-chat.messages.store'), ['message' => 'hello'])
            ->assertOk()
            ->assertJsonPath('intent', 'greeting');

        $this->assertStringNotContainsString('private-invalid-json', $response->getContent());
    }

    /** @param array<string,mixed> $payload */
    private function providerResponse(array $payload): PromiseInterface
    {
        return Http::response(['candidates' => [[
            'content' => ['parts' => [['text' => json_encode($payload, JSON_THROW_ON_ERROR)]]],
        ]]]);
    }
}
