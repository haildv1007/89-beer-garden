<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostCategoryController extends Controller
{
    public function index()
    {
        $categories = PostCategory::query()->orderBy('sort_order')->orderBy('id')->get();
        $counts = Post::query()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');

        return view('admin.posts.categories', compact('categories', 'counts'));
    }

    public function create()
    {
        return view('admin.posts.category-form', [
            'category' => new PostCategory(['sort_order' => min(999999, (int) PostCategory::max('sort_order') + 10)]),
        ]);
    }

    public function edit(PostCategory $postCategory)
    {
        return view('admin.posts.category-form', ['category' => $postCategory]);
    }

    public function store(Request $request)
    {
        PostCategory::create($this->validatedData($request));

        return redirect()->route('admin.post-categories.index')->with('success', 'Đã thêm chuyên mục.');
    }

    public function update(Request $request, PostCategory $postCategory)
    {
        $data = $this->validatedData($request, $postCategory);
        DB::transaction(function () use ($postCategory, $data) {
            Post::withTrashed()->where('category', $postCategory->slug)->update(['category' => $data['slug']]);
            $postCategory->update($data);
        });

        return redirect()->route('admin.post-categories.index')->with('success', 'Đã cập nhật chuyên mục.');
    }

    private function validatedData(Request $request, ?PostCategory $category = null): array
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->input('slug') ?: $request->input('name'))),
            'sort_order' => $request->input('sort_order', $category?->sort_order ?? 0),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:40', 'alpha_dash:ascii', Rule::unique('post_categories', 'slug')->ignore($category?->id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);
    }

    public function destroy(PostCategory $postCategory)
    {
        if (Post::withTrashed()->where('category', $postCategory->slug)->exists()) {
            return back()->withErrors(['category' => 'Chuyên mục còn bài viết. Hãy chuyển bài sang chuyên mục khác trước khi xóa.']);
        }
        $postCategory->delete();

        return redirect()->route('admin.post-categories.index')->with('success', 'Đã xóa chuyên mục.');
    }
}
