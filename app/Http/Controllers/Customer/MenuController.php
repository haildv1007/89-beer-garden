<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MenuIndexRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(
        MenuIndexRequest $request,
        DynamicTranslationResolver $resolver,
    ): View {
        $filters = $request->validated();
        $products = Product::query()
            ->publicMenu()
            ->with(['category', 'media'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['category'] ?? null,
                fn (Builder $query, string $slug) => $query->whereHas(
                    'category',
                    fn (Builder $category) => $category->where('slug', $slug),
                ),
            )
            ->orderBy('name')
            ->get();

        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $menuSections = $categories
            ->map(
                fn (Category $category): array => [
                    'category' => $category,
                    'products' => $products->where('category_id', $category->id)->values(),
                ],
            )
            ->filter(fn (array $section): bool => $section['products']->isNotEmpty())
            ->values();

        return view('customer.menu.index', [
            'categories' => $categories,
            'products' => $products,
            'menuSections' => $menuSections,
            'filters' => $filters,
            'dynamicTranslations' => $resolver->batch(
                $categories
                    ->concat($products)
                    ->concat($products->pluck('category'))
                    ->unique(fn ($e) => $e::class.':'.$e->id),
            ),
        ]);
    }
}
