<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('customer.home', [
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'products' => Product::query()->publicMenu()->with('category')->latest()->limit(6)->get(),
        ]);
    }
}
