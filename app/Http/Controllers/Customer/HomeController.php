<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(DynamicTranslationResolver $resolver): View
    {
        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $publicProducts = Product::query()
            ->publicMenu()
            ->with(['category', 'media'])
            ->latest()
            ->get()
            ->values();

        $categorySearchKey = fn (Category $category): string => Str::slug($category->name.' '.$category->slug);
        $featuredCategory =
            $categories->first(fn (Category $category) => str_contains($categorySearchKey($category), 'mon-hot')) ??
            ($categories->first(fn (Category $category) => str_contains($categorySearchKey($category), 'mon-nuong')) ??
                $publicProducts->pluck('category')->filter()->first());

        $products = $featuredCategory
            ? $publicProducts->where('category_id', $featuredCategory->id)->take(16)->values()
            : collect();
        $latestPosts = Post::query()
            ->published()
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $translationEntities = $categories
            ->concat($products)
            ->concat($products->pluck('category'))
            ->concat(collect([$featuredCategory])->filter())
            ->unique(fn ($entity) => $entity::class.':'.$entity->id);

        return view(
            'customer.home',
            compact('categories', 'products', 'featuredCategory', 'latestPosts') + [
                'dynamicTranslations' => $resolver->batch($translationEntities),
            ],
        );
    }
}
