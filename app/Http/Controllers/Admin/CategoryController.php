<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
        $categories = Category::query()
            ->withCount('products')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('admin.categories.create', ['category' => new Category]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create($request->validated());

        return redirect()->route('admin.categories.show', $category)->with('success', __('app.saved'));
    }

    public function show(Category $category): View
    {
        $category->loadCount('products');

        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.show', $category)->with('success', __('app.saved'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, __('app.categories.delete_blocked'));

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', __('app.deleted'));
    }
}
