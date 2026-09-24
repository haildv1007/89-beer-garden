<?php

namespace Tests\Feature\Customer;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_search_filter_and_view_product_detail(): void
    {
        $beer = $this->category('Beer', 'beer');
        $food = $this->category('Food', 'food');
        $lager = $this->product(
            $beer,
            'Saigon Lager',
            'saigon-lager',
            50000,
            description: 'Long-form SEO product information.',
            shortDescription: 'Crisp beer',
        );
        $this->product($food, 'Fries', 'fries', 35000);

        $this->get(route('customer.home'))->assertOk()->assertSee('Saigon Lager')->assertSee('50.000 ₫');
        $this->get(route('customer.menu.index', ['q' => 'Crisp', 'category' => 'beer']))
            ->assertOk()
            ->assertSee('Saigon Lager')
            ->assertDontSee('Fries')
            ->assertDontSee(__('customer_order.context_required'));
        $this->get(route('customer.products.show', $lager))
            ->assertOk()
            ->assertSee('Saigon Lager')
            ->assertSee('Crisp beer')
            ->assertSee('Long-form SEO product information.')
            ->assertSee('50.000 ₫')
            ->assertSee(__('customer_ui.related_eyebrow'))
            ->assertSee('Fries');
    }

    public function test_public_menu_hides_inactive_or_soft_deleted_data_but_shows_unavailable_feedback(): void
    {
        $active = $this->category('Active', 'active');
        $inactive = $this->category('Inactive', 'inactive', Category::STATUS_INACTIVE);
        $visibleUnavailable = $this->product($active, 'Temporarily Sold Out', 'sold-out', 10000, false);
        $timeTag = Tag::query()->create(['name' => 'TEST 99P', 'slug' => 'test-99p', 'sort_order' => 1]);
        $visibleUnavailable->tags()->attach($timeTag);
        $this->product($active, 'Inactive Product', 'inactive-product', 10000, status: Product::STATUS_INACTIVE);
        $this->product($inactive, 'Hidden Category Product', 'hidden-category', 10000);
        $deleted = $this->product($active, 'Deleted Product', 'deleted-product', 10000);
        $deleted->delete();

        $response = $this->get(route('customer.menu.index'));
        $response
            ->assertOk()
            ->assertSee($visibleUnavailable->name)
            ->assertSee(__('app.products.unavailable'))
            ->assertSee('product-card--unavailable')
            ->assertDontSee('product-tag--'.$timeTag->slug)
            ->assertDontSee('Inactive Product')
            ->assertDontSee('Hidden Category Product')
            ->assertDontSee('Deleted Product');
        $this->get('/products/inactive-product')->assertNotFound();
        $this->get('/products/deleted-product')->assertNotFound();
    }

    public function test_homepage_prioritizes_products_with_hot_tag_across_categories(): void
    {
        $food = $this->category('Món ăn', 'mon-an');
        $seafood = $this->category('Hải sản', 'hai-san');
        $regular = $this->product($food, 'Món thường', 'mon-thuong', 50000);
        $hot = Tag::query()->create(['name' => 'HOT', 'slug' => 'hot', 'sort_order' => 1]);
        $featuredProducts = collect(range(1, 10))->map(function (int $index) use ($seafood, $hot): Product {
            $product = $this->product($seafood, "Món nổi bật {$index}", "mon-noi-bat-{$index}", 120000);
            $product->tags()->attach($hot);

            return $product;
        });
        $unavailableHot = $this->product($seafood, 'Món HOT tạm hết', 'mon-hot-tam-het', 90000, false);
        $unavailableHot->tags()->attach($hot);

        $this->get(route('customer.home'))
            ->assertOk()
            ->assertSee('data-carousel-column', false)
            ->assertViewHas('products', function ($products) use ($featuredProducts): bool {
                return $products->count() === 10
                    && $products->every(fn (Product $product): bool => $featuredProducts->contains('id', $product->id));
            })
            ->assertDontSee($unavailableHot->name)
            ->assertDontSee($regular->name);
    }

    public function test_empty_state_and_invalid_filter_are_safe(): void
    {
        $this->get(route('customer.menu.index'))->assertOk()->assertSee(__('app.menu.no_results'));
        $this->get(route('customer.menu.index', ['category' => 'missing']))
            ->assertRedirect()
            ->assertSessionHasErrors('category');
        $this->get(route('customer.menu.index', ['q' => ['not-a-string']]))
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    public function test_customer_brand_shell_and_product_fallback_render_in_every_locale(): void
    {
        $category = $this->category('Đồ nướng', 'do-nuong');
        $product = $this->product($category, '<script>alert(1)</script>', 'safe-product', 125000);

        foreach (['vi', 'en', 'zh'] as $locale) {
            $response = $this->withSession(['locale' => $locale])->get(route('customer.home'));

            $response
                ->assertOk()
                ->assertSee(__('customer_ui.hero_heading', locale: $locale))
                ->assertSee(__('customer_ui.footer_reservation', locale: $locale))
                ->assertSee(__('customer_ui.image_fallback', ['category' => $category->name], $locale))
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
                ->assertDontSee('<script>alert(1)</script>', false);
        }

        $this->get(route('customer.products.show', $product))
            ->assertOk()
            ->assertSee(__('customer_order.add_to_cart'))
            ->assertDontSee(__('customer_ui.open_table_link'));
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
        ?string $shortDescription = null,
    ): Product {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'price' => $price,
            'status' => $status,
            'is_available' => $available,
        ]);
    }
}
