<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductPriceRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
        $category = is_string($request->query('category')) ? $request->query('category') : '';
        $status = in_array($request->query('status'), [Product::STATUS_ACTIVE, Product::STATUS_INACTIVE], true)
            ? $request->query('status') : '';

        $products = Product::query()->with('category')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            }))
            ->when($category !== '', fn (Builder $query) => $query->where('category_id', $category))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => compact('search', 'category', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($request->validated());

        return redirect()->route('admin.products.show', $product)->with('success', __('app.saved'));
    }

    public function show(Product $product): View
    {
        $product->load('category');

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('admin.products.show', $product)->with('success', __('app.saved'));
    }

    public function updatePrice(UpdateProductPriceRequest $request, Product $product): RedirectResponse
    {
        $product->update(['price' => $request->integer('price')]);

        return redirect()->route('admin.products.show', $product)->with('success', __('app.saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', __('app.deleted'));
    }
}
