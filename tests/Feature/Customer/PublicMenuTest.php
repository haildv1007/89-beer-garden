<?php

namespace Tests\Feature\Customer;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_search_filter_and_view_product_detail(): void
    {
        $beer = $this->category('Beer', 'beer');
        $food = $this->category('Food', 'food');
        $lager = $this->product($beer, 'Saigon Lager', 'saigon-lager', 50000, description: 'Crisp beer');
        $this->product($food, 'Fries', 'fries', 35000);

        $this->get(route('customer.home'))->assertOk()->assertSee('Saigon Lager')->assertSee('50.000 ₫');
        $this->get(route('customer.menu.index', ['q' => 'Crisp', 'category' => 'beer']))
            ->assertOk()->assertSee('Saigon Lager')->assertDontSee('Fries');
        $this->get(route('customer.products.show', $lager))
            ->assertOk()->assertSee('Saigon Lager')->assertSee('50.000 ₫');
    }

    public function test_public_menu_hides_inactive_or_soft_deleted_data_but_shows_unavailable_feedback(): void
    {
        $active = $this->category('Active', 'active');
        $inactive = $this->category('Inactive', 'inactive', Category::STATUS_INACTIVE);
        $visibleUnavailable = $this->product($active, 'Temporarily Sold Out', 'sold-out', 10000, false);
        $this->product($active, 'Inactive Product', 'inactive-product', 10000, status: Product::STATUS_INACTIVE);
        $this->product($inactive, 'Hidden Category Product', 'hidden-category', 10000);
        $deleted = $this->product($active, 'Deleted Product', 'deleted-product', 10000);
        $deleted->delete();

        $response = $this->get(route('customer.menu.index'));
        $response->assertOk()->assertSee($visibleUnavailable->name)->assertSee(__('app.products.unavailable'))
            ->assertDontSee('Inactive Product')->assertDontSee('Hidden Category Product')->assertDontSee('Deleted Product');
        $this->get('/products/inactive-product')->assertNotFound();
        $this->get('/products/deleted-product')->assertNotFound();
    }

    public function test_empty_state_and_invalid_filter_are_safe(): void
    {
        $this->get(route('customer.menu.index'))->assertOk()->assertSee(__('app.menu.no_results'));
        $this->get(route('customer.menu.index', ['category' => 'missing']))
            ->assertRedirect()->assertSessionHasErrors('category');
        $this->get(route('customer.menu.index', ['q' => ['not-a-string']]))
            ->assertRedirect()->assertSessionHasErrors('q');
    }

    private function category(string $name, string $slug, string $status = Category::STATUS_ACTIVE): Category
    {
        return Category::query()->create(compact('name', 'slug', 'status') + ['sort_order' => 0]);
    }

    private function product(
        Category $category,
        string $name,
        string $slug,
        int $price,
        bool $available = true,
        ?string $description = null,
        string $status = Product::STATUS_ACTIVE,
    ): Product {
        return Product::query()->create([
            'category_id' => $category->id, 'name' => $name, 'slug' => $slug,
            'description' => $description, 'price' => $price, 'status' => $status,
            'is_available' => $available,
        ]);
    }
}
