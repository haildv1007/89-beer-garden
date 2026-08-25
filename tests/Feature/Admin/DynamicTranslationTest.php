<?php

namespace Tests\Feature\Admin;

use App\Contracts\GoogleTranslationClient;
use App\Contracts\TranslationProvider;
use App\Enums\EmployeeStatus;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;
use App\Models\Translation;
use App\Models\User;
use App\Services\Translation\DynamicTranslationResolver;
use App\Services\Translation\GoogleCloudTranslationProvider;
use App\Services\Translation\SaveManualTranslationService;
use App\Services\Translation\TranslationCallBudget;
use App\Services\Translation\TranslationProviderResult;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_vi_invalid_and_missing_translation_fall_back_without_writes(): void
    {
        $product = $this->product('Bia Việt');
        $resolver = app(DynamicTranslationResolver::class);
        $this->assertSame('Bia Việt', $resolver->resolve($product, 'name', 'vi'));
        $this->assertSame('Bia Việt', $resolver->resolve($product, 'name', 'fr'));
        $this->assertSame('Bia Việt', $resolver->resolve($product, 'name', 'en'));
        $this->assertDatabaseCount('translations', 0);
    }

    public function test_persistent_provider_translation_is_used_and_stale_provider_falls_back(): void
    {
        $product = $this->product('Bia Việt');
        Translation::query()->forceCreate($this->translation($product, 'Beer', 'provider'));
        $resolver = app(DynamicTranslationResolver::class);
        $this->assertSame('Beer', $resolver->resolve($product, 'name', 'en'));
        $product->forceFill(['name' => 'Bia mới'])->save();
        $this->assertSame('Bia mới', $resolver->resolve($product->fresh(), 'name', 'en'));
    }

    public function test_provider_success_persists_and_prevents_repeated_calls(): void
    {
        $provider = new class implements TranslationProvider
        {
            public int $calls = 0;

            public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
            {
                $this->calls++;

                return TranslationProviderResult::success('Beer');
            }
        };
        $this->app->instance(TranslationProvider::class, $provider);
        $product = $this->product('Bia');
        $resolver = app(DynamicTranslationResolver::class);
        $this->assertSame('Beer', $resolver->resolve($product, 'name', 'en'));
        $this->assertSame('Beer', $resolver->resolve($product, 'name', 'en'));
        $this->assertSame(1, $provider->calls);
        $this->assertDatabaseHas('translations', ['source' => 'provider', 'translated_text' => 'Beer', 'updated_by_employee_id' => null]);
    }

    public function test_manual_stale_remains_authoritative_and_admin_can_convert_provider_row(): void
    {
        $product = $this->product('Bia');
        Translation::query()->forceCreate($this->translation($product, 'Machine beer', 'provider'));
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.translations.store'), ['entity_type' => 'product',
            'entity_id' => $product->id, 'field' => 'name', 'locale' => 'en', 'translated_text' => ' Manual beer '])->assertRedirect();
        $row = Translation::query()->sole();
        $this->assertSame(['Manual beer', 'manual', $admin->employee->id], [$row->translated_text, $row->source, $row->updated_by_employee_id]);
        $product->forceFill(['name' => 'Bia đổi'])->save();
        $this->assertSame('Manual beer', app(DynamicTranslationResolver::class)->resolve($product->fresh(), 'name', 'en'));
        $this->get(route('admin.translations.index'))->assertOk()->assertSee(__('translation.stale'));
    }

    public function test_admin_validation_authorization_and_forged_fields_are_enforced(): void
    {
        $product = $this->product();
        $payload = ['entity_type' => 'product', 'entity_id' => $product->id, 'field' => 'name', 'locale' => 'en', 'translated_text' => 'Beer'];
        $this->actingAs($this->user('manager'))->post(route('admin.translations.store'), $payload)->assertForbidden();
        $admin = $this->user('admin');
        foreach ([['entity_type' => Product::class], ['field' => 'price'], ['locale' => 'vi'], ['source_hash' => 'forged']] as $forged) {
            $this->actingAs($admin)->post(route('admin.translations.store'), array_merge($payload, $forged))->assertSessionHasErrors(array_key_first($forged));
        }
        $this->assertDatabaseCount('translations', 0);
    }

    public function test_menu_uses_escaped_dynamic_translations_and_soft_deleted_entity_falls_back(): void
    {
        $product = $this->product('Bia');
        Translation::query()->forceCreate($this->translation($product, '<script>alert(1)</script>', 'manual'));
        $this->withSession(['locale' => 'en'])->get(route('customer.menu.index'))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $product->delete();
        $this->assertSame('Bia', app(DynamicTranslationResolver::class)->resolve($product, 'name', 'en'));
    }

    public function test_manual_created_during_provider_call_wins(): void
    {
        $product = $this->product('Bia');
        $admin = $this->user('admin');
        $provider = new class(function () use ($product, $admin): void {
            app(SaveManualTranslationService::class)->save('product', $product->id, 'name', 'en', 'Manual winner', $admin);
        }) implements TranslationProvider {

            public function __construct(private \Closure $callback) {}

            public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
            {
                ($this->callback)();

                return TranslationProviderResult::success('Provider loser');
            }
        };
        $this->app->instance(TranslationProvider::class, $provider);
        $this->assertSame('Manual winner', app(DynamicTranslationResolver::class)->resolve($product, 'name', 'en'));
        $this->assertDatabaseHas('translations', ['source' => 'manual', 'translated_text' => 'Manual winner']);
    }

    public function test_source_change_during_provider_call_does_not_persist_stale_result(): void
    {
        $product = $this->product('Bia cũ');
        $provider = new class(function () use ($product): void {
            $product->forceFill(['name' => 'Bia mới'])->save();
        }) implements TranslationProvider {

            public function __construct(private \Closure $callback) {}

            public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
            {
                ($this->callback)();

                return TranslationProviderResult::success('Old beer');
            }
        };
        $this->app->instance(TranslationProvider::class, $provider);
        $this->assertSame('Bia cũ', app(DynamicTranslationResolver::class)->resolve($product, 'name', 'en'));
        $this->assertDatabaseCount('translations', 0);
    }

    public function test_customer_menu_automatically_translates_with_request_budget(): void
    {
        config()->set('translation.google.max_calls_per_request', 2);
        $this->product('Bia một');
        $this->product('Bia hai');
        $client = new class implements GoogleTranslationClient
        {
            public int $calls = 0;

            public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
            {
                $this->calls++;

                return 'EN '.$text;
            }
        };
        $this->app->instance(TranslationProvider::class,
            new GoogleCloudTranslationProvider($client, new TranslationCallBudget));

        $this->withSession(['locale' => 'en'])->get(route('customer.menu.index'))->assertOk()->assertSee('EN Đồ uống');
        $this->assertSame(2, $client->calls);
        $this->assertDatabaseCount('translations', 2);
    }

    public function test_chinese_translation_is_persisted_as_zh_and_reused_without_another_call(): void
    {
        $product = $this->product('Bia Việt');
        $client = new class implements GoogleTranslationClient
        {
            public int $calls = 0;

            public array $locales = [];

            public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
            {
                $this->calls++;
                $this->locales[] = [$sourceLocale, $targetLocale];

                return '越南啤酒';
            }
        };
        $this->app->instance(TranslationProvider::class,
            new GoogleCloudTranslationProvider($client, new TranslationCallBudget));
        $resolver = app(DynamicTranslationResolver::class);

        $this->assertSame('越南啤酒', $resolver->resolve($product, 'name', 'zh'));
        $this->assertSame('越南啤酒', $resolver->resolve($product, 'name', 'zh'));
        $this->assertSame(1, $client->calls);
        $this->assertSame([['vi', 'zh-CN']], $client->locales);
        $this->assertDatabaseHas('translations', [
            'translatable_type' => Product::class,
            'translatable_id' => $product->id,
            'field' => 'name',
            'locale' => 'zh',
            'translated_text' => '越南啤酒',
            'source' => 'provider',
        ]);
    }

    public function test_stale_provider_translation_is_refreshed_without_creating_a_duplicate(): void
    {
        $product = $this->product('Bia cũ');
        Translation::query()->forceCreate($this->translation($product, 'Old beer', 'provider'));
        $product->forceFill(['name' => 'Bia mới'])->save();
        $provider = new class implements TranslationProvider
        {
            public int $calls = 0;

            public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
            {
                $this->calls++;

                return TranslationProviderResult::success('New beer');
            }
        };
        $this->app->instance(TranslationProvider::class, $provider);

        $this->assertSame('New beer', app(DynamicTranslationResolver::class)->resolve($product->fresh(), 'name', 'en'));
        $this->assertSame(1, $provider->calls);
        $this->assertDatabaseCount('translations', 1);
        $this->assertDatabaseHas('translations', [
            'source_text' => 'Bia mới',
            'source_hash' => hash('sha256', 'Bia mới'),
            'translated_text' => 'New beer',
            'source' => 'provider',
        ]);
    }

    public function test_vi_manual_and_persistent_hits_do_not_consume_google_budget(): void
    {
        config()->set('translation.google.max_calls_per_request', 1);
        $manual = $this->product('Bia thủ công');
        $persistent = $this->product('Bia đã dịch');
        $missing = $this->product('Bia mới');
        Translation::query()->forceCreate($this->translation($manual, 'Manual beer', 'manual'));
        Translation::query()->forceCreate($this->translation($persistent, 'Stored beer', 'provider'));
        $client = new class implements GoogleTranslationClient
        {
            public int $calls = 0;

            public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
            {
                $this->calls++;

                return 'Fresh beer';
            }
        };
        $this->app->instance(TranslationProvider::class,
            new GoogleCloudTranslationProvider($client, new TranslationCallBudget));
        $resolver = app(DynamicTranslationResolver::class);

        $this->assertSame('Bia mới', $resolver->resolve($missing, 'name', 'vi'));
        $this->assertSame('Manual beer', $resolver->resolve($manual, 'name', 'en'));
        $this->assertSame('Stored beer', $resolver->resolve($persistent, 'name', 'en'));
        $this->assertSame('Fresh beer', $resolver->resolve($missing, 'name', 'en'));
        $this->assertSame(1, $client->calls);
    }

    public function test_internal_context_pages_never_invoke_translation_provider(): void
    {
        $this->product('Không được gửi sang Google');
        $provider = new class implements TranslationProvider
        {
            public int $calls = 0;

            public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
            {
                $this->calls++;

                return TranslationProviderResult::success('Unexpected');
            }
        };
        $this->app->instance(TranslationProvider::class, $provider);

        foreach (['admin' => 'admin.home', 'staff' => 'pos.home', 'kitchen' => 'kitchen.home'] as $role => $route) {
            $this->actingAs($this->user($role))->withSession(['locale' => 'en'])->get(route($route))->assertOk();
        }

        $this->assertSame(0, $provider->calls);
        $this->assertDatabaseCount('translations', 0);
    }

    private function translation(Product $product, string $text, string $source): array
    {
        return ['translatable_type' => Product::class, 'translatable_id' => $product->id, 'field' => 'name', 'locale' => 'en',
            'source_text' => $product->name, 'translated_text' => $text, 'source_hash' => hash('sha256', $product->name), 'source' => $source];
    }

    private function product(string $name = 'Product'): Product
    {
        $category = Category::query()->forceCreate(['name' => 'Đồ uống', 'slug' => 'drink-'.fake()->unique()->numberBetween(1, 99999), 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1]);

        return Product::query()->forceCreate(['category_id' => $category->id, 'name' => $name, 'slug' => 'p-'.fake()->unique()->numberBetween(1, 99999), 'price' => 10000, 'status' => Product::STATUS_ACTIVE, 'is_available' => true]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->forRole(Role::where('code', $role)->firstOrFail())->create();
        Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => 'TR-'.fake()->unique()->numberBetween(1, 99999), 'name' => $role, 'status' => EmployeeStatus::Active]);

        return $user;
    }
}
