<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_manage_tags_and_assign_many_tags_to_a_product(): void
    {
        $admin = User::factory()->forRole(Role::where('code', 'admin')->firstOrFail())->create();
        Employee::forceCreate(['user_id' => $admin->id, 'employee_code' => 'TAG-1', 'name' => 'Admin', 'status' => EmployeeStatus::Active]);

        $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'HOT', 'slug' => 'hot', 'icon_class' => 'ti ti-flame', 'sort_order' => 1,
        ])->assertRedirect();
        $this->post(route('admin.tags.store'), [
            'name' => '10P', 'slug' => '10p', 'icon_class' => 'ti ti-clock-10', 'sort_order' => 2,
        ])->assertRedirect();
        $this->post(route('admin.tags.store'), [
            'name' => 'Không icon', 'slug' => 'khong-icon', 'icon_class' => '', 'sort_order' => 3,
        ])->assertRedirect();
        $this->assertDatabaseHas('tags', ['slug' => 'khong-icon', 'icon_class' => null]);

        $category = Category::query()->create(['name' => 'Món ăn', 'slug' => 'mon-an', 'status' => 'active', 'sort_order' => 1]);
        $tagIds = Tag::query()->pluck('id')->all();
        $this->post(route('admin.products.store'), [
            'category_id' => $category->id, 'name' => 'Trứng cút lộn', 'slug' => 'trung-cut-lon',
            'short_description' => 'Mô tả ngắn', 'description' => 'Mô tả dài', 'price' => 60000,
            'status' => 'active', 'is_available' => '1', 'tags' => $tagIds,
        ])->assertRedirect();

        $product = Product::where('slug', 'trung-cut-lon')->firstOrFail();
        $this->assertEqualsCanonicalizing($tagIds, $product->tags()->pluck('tags.id')->all());
        $this->get(route('customer.menu.index', ['tag' => 'hot']))->assertOk()->assertSee('Trứng cút lộn');

        $product->variants()->create(['name' => 'Set 400K', 'price' => 400000, 'is_available' => true, 'sort_order' => 0]);
        $this->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'status' => 'active',
            'is_available' => '1',
            'tags' => $tagIds,
        ])->assertRedirect();
        $this->assertCount(0, $product->variants()->get(), 'Tắt công tắc phải gỡ các biến thể cũ.');

        $hot = Tag::where('slug', 'hot')->firstOrFail();
        $this->put(route('admin.tags.update', $hot), [
            'name' => 'Món nổi bật', 'slug' => 'hot', 'icon_class' => 'ti ti-star', 'sort_order' => 1,
        ])->assertRedirect();
        $this->delete(route('admin.tags.destroy', $hot))->assertRedirect();
        $this->assertDatabaseMissing('tags', ['id' => $hot->id]);
    }
}
