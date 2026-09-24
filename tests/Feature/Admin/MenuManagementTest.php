<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_menu_routes_enforce_context_employee_and_resource_permissions(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
        foreach (['customer', 'staff', 'kitchen'] as $role) {
            $this->actingAs($this->user($role, $role !== 'customer'))
                ->get(route('admin.categories.index'))
                ->assertForbidden();
        }

        $manager = $this->user('manager', true);
        $this->actingAs($manager)->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $manager->role->permissions()->detach(Permission::where('code', 'category.manage')->firstOrFail());
        $this->get(route('admin.categories.index'))->assertForbidden();

        $managerWithoutProducts = $this->user('manager', true);
        $managerWithoutProducts->role
            ->permissions()
            ->detach(Permission::where('code', 'product.manage')->firstOrFail());
        $this->actingAs($managerWithoutProducts)->get(route('admin.products.index'))->assertForbidden();

        $disabled = $this->user('admin', true, EmployeeStatus::Disabled);
        $this->actingAs($disabled)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_category_crud_validates_unique_slug_and_soft_deletes(): void
    {
        $admin = $this->user('admin', true);
        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Beer',
                'slug' => 'beer',
                'description' => 'Cold beer',
                'status' => 'active',
                'sort_order' => 1,
            ])
            ->assertRedirect();
        $category = Category::where('slug', 'beer')->firstOrFail();

        $this->post(route('admin.categories.store'), [
            'name' => 'Duplicate',
            'slug' => 'beer',
            'status' => 'invalid',
            'sort_order' => -1,
        ])->assertSessionHasErrors(['slug', 'status', 'sort_order']);
        $this->put(route('admin.categories.update', $category), [
            'name' => 'Craft Beer',
            'slug' => 'craft-beer',
            'status' => 'inactive',
            'sort_order' => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'craft-beer', 'status' => 'inactive']);
        $this->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_products_is_protected_from_deletion(): void
    {
        $admin = $this->user('admin', true);
        $category = $this->category();
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Beer',
            'slug' => 'protected-beer',
            'price' => 10000,
            'status' => 'active',
            'is_available' => true,
        ]);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertUnprocessable();
        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_product_crud_validates_business_fields_and_generic_update_cannot_change_price(): void
    {
        $admin = $this->user('admin', true);
        $category = $this->category();
        $payload = [
            'category_id' => $category->id,
            'name' => 'Lager',
            'slug' => 'lager',
            'description' => null,
            'image_url' => null,
            'price' => 50000,
            'status' => 'active',
            'is_available' => '1',
        ];

        $this->actingAs($admin)->post(route('admin.products.store'), $payload)->assertRedirect();
        $product = Product::where('slug', 'lager')->firstOrFail();
        $this->post(route('admin.products.store'), $payload + ['slug' => 'lager'])->assertSessionHasErrors('slug');
        $this->post(
            route('admin.products.store'),
            array_merge($payload, ['slug' => 'negative', 'price' => -1]),
        )->assertSessionHasErrors('price');

        $this->put(
            route('admin.products.update', $product),
            array_merge($payload, [
                'name' => 'Updated Lager',
                'price' => 1,
                'is_available' => '0',
                'status' => 'inactive',
            ]),
        )->assertSessionHasErrors('price');
        $this->assertSame(50000, $product->fresh()->price);

        unset($payload['price']);
        $this->put(
            route('admin.products.update', $product),
            array_merge($payload, [
                'name' => 'Updated Lager',
                'is_available' => '0',
                'status' => 'inactive',
            ]),
        )->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Lager',
            'is_available' => false,
            'status' => 'inactive',
        ]);
        $this->delete(route('admin.products.destroy', $product))->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_price_endpoint_requires_both_permissions_and_preserves_historical_snapshot(): void
    {
        $category = $this->category();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Lager',
            'slug' => 'lager',
            'price' => 50000,
            'status' => 'active',
            'is_available' => true,
        ]);
        $admin = $this->user('admin', true);
        $orderItemId = $this->historicalOrderItem($admin->employee, $product, 50000);

        $withoutPrice = $this->user('manager', true);
        $withoutPrice->role->permissions()->detach(Permission::where('code', 'product.update-price')->firstOrFail());
        $this->actingAs($withoutPrice)
            ->patch(route('admin.products.price.update', $product), ['price' => 55000])
            ->assertForbidden();
        $this->assertSame(50000, $product->fresh()->price);

        $this->actingAs($admin)
            ->patch(route('admin.products.price.update', $product), ['price' => 55000])
            ->assertRedirect();
        $this->assertSame(55000, $product->fresh()->price);
        $this->assertSame(50000, (int) DB::table('order_items')->where('id', $orderItemId)->value('unit_price'));
    }

    public function test_price_action_is_hidden_without_permission_while_forged_request_is_denied(): void
    {
        $product = Product::query()->create([
            'category_id' => $this->category()->id,
            'name' => 'Beer',
            'slug' => 'beer',
            'price' => 10000,
            'status' => 'active',
            'is_available' => true,
        ]);
        $manager = $this->user('manager', true);
        $manager->role->permissions()->detach(Permission::where('code', 'product.update-price')->firstOrFail());

        $this->actingAs($manager)
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertDontSee(__('app.products.update_price'));
        $this->get(route('admin.products.create'))->assertForbidden();
        $this->patch(route('admin.products.price.update', $product), ['price' => 1])->assertForbidden();
    }

    public function test_product_accepts_at_most_five_image_or_video_files_and_can_replace_them(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin', true);
        $category = $this->category();
        $payload = [
            'category_id' => $category->id,
            'name' => 'Media platter',
            'slug' => 'media-platter',
            'description' => null,
            'price' => 150000,
            'status' => 'active',
            'is_available' => '1',
        ];
        $files = [
            1 => UploadedFile::fake()->create('one.jpg', 20, 'image/jpeg'),
            2 => UploadedFile::fake()->create('two.png', 20, 'image/png'),
            3 => UploadedFile::fake()->create('three.webp', 20, 'image/webp'),
            4 => UploadedFile::fake()->create('four.mp4', 100, 'video/mp4'),
            5 => UploadedFile::fake()->create('five.webm', 100, 'video/webm'),
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload + ['media_slots' => $files])
            ->assertRedirect();
        $product = Product::query()->where('slug', 'media-platter')->firstOrFail();
        $this->assertSame(5, $product->media()->count());
        $this->assertSame(2, $product->media()->where('media_type', 'video')->count());
        $this->assertSame([0, 1, 2, 3, 4], $product->media->pluck('sort_order')->all());
        $product->media->each(fn ($media) => Storage::disk('public')->assertExists($media->path));
        $firstMedia = $product->media->first();
        $this->get(route('customer.product-media.show', $firstMedia))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $reversedIds = $product->media->pluck('id')->reverse()->values()->all();
        $this->put(
            route('admin.products.update', $product),
            Arr::except($payload, ['price']) + [
                'media_order' => $reversedIds,
            ],
        )->assertRedirect();
        $this->assertSame($reversedIds, $product->fresh()->media->pluck('id')->all());
        $replacedMedia = $product->fresh()->media->first();

        $this->put(
            route('admin.products.update', $product),
            Arr::except($payload, ['price']) + [
                'media_slots' => [1 => UploadedFile::fake()->create('replacement.jpg', 20, 'image/jpeg')],
            ],
        )->assertRedirect();
        $this->assertSame(5, $product->fresh()->media()->count());
        Storage::disk('public')->assertMissing($replacedMedia->path);
        $this->assertSame('replacement.jpg', $product->fresh()->media->first()->original_name);

        $this->put(
            route('admin.products.update', $product),
            Arr::except($payload, ['price']) + [
                'media_slots' => [6 => UploadedFile::fake()->create('invalid.jpg', 20, 'image/jpeg')],
            ],
        )->assertSessionHasErrors('media_slots');
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => 'Beer',
            'slug' => 'beer-'.fake()->unique()->numberBetween(1, 999999),
            'status' => 'active',
            'sort_order' => 0,
        ]);
    }

    private function user(string $role, bool $employee = false, EmployeeStatus $status = EmployeeStatus::Active): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        if ($employee) {
            Employee::forceCreate([
                'user_id' => $user->id,
                'employee_code' => 'E-'.$user->id,
                'name' => 'Employee',
                'status' => $status,
            ]);
        }

        return $user;
    }

    private function historicalOrderItem(Employee $employee, Product $product, int $price): int
    {
        $now = now();
        $tableId = DB::table('restaurant_tables')->insertGetId([
            'code' => 'T01',
            'name' => 'Table 1',
            'capacity' => 4,
            'runtime_status' => 'occupied',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sessionId = DB::table('dining_sessions')->insertGetId([
            'session_code' => 'S01',
            'table_id' => $tableId,
            'opened_by_employee_id' => $employee->id,
            'status' => 'active',
            'started_at' => $now,
            'guest_count' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'order_code' => 'O01',
            'dining_session_id' => $sessionId,
            'created_by_employee_id' => $employee->id,
            'source' => 'staff',
            'ordered_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('order_items')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $price,
            'line_total' => $price,
            'status' => 'waiting',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
