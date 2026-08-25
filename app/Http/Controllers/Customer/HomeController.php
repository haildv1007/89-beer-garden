<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(DynamicTranslationResolver $resolver): View
    {
        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $products = Product::query()->publicMenu()->with('category')->latest()->limit(6)->get();

        return view('customer.home', compact('categories', 'products') + ['dynamicTranslations' => $resolver->batch($categories->concat($products)->concat($products->pluck('category'))->unique(fn ($e) => $e::class.':'.$e->id))]);
    }
}
