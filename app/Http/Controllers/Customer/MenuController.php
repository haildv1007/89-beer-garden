<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MenuIndexRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CustomerOrder\CustomerDiningContextService;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(MenuIndexRequest $request, CustomerDiningContextService $context, DynamicTranslationResolver $resolver): View
    {
        $filters = $request->validated();
        $products = Product::query()
            ->publicMenu()
            ->with('category')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $slug)))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('customer.menu.index', [
            'categories' => $categories,
            'products' => $products,
            'filters' => $filters,
            'customerOrderingAvailable' => $context->available($request),
            'dynamicTranslations' => $resolver->batch($categories->concat($products->getCollection())->concat($products->pluck('category'))->unique(fn ($e) => $e::class.':'.$e->id)),
        ]);
    }
}
