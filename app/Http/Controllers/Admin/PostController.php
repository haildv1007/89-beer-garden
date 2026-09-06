<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\PostHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);
        $status = in_array($request->query('status'), [Post::STATUS_DRAFT, Post::STATUS_PUBLISHED], true)
            ? $request->query('status')
            : null;
        $category = in_array($request->query('category'), array_keys(PostCategory::labels()), true)
            ? $request->query('category')
            : null;

        $posts = Post::query()
            ->with('author.employee')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'search', 'status', 'category'));
    }

    public function create(): View
    {
        return view('admin.posts.create', ['post' => new Post]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $post = Post::query()->create($this->payload($request));

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Đã tạo bài viết.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', compact('post'));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $this->payload($request, $post);

        if (
            $request->boolean('remove_featured_image') &&
            ! $request->hasFile('featured_image') &&
            $post->featured_image_path
        ) {
            Storage::disk('public')->delete($post->featured_image_path);
            $data['featured_image_path'] = null;
        }

        $post->update($data);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->featured_image_path) {
            Storage::disk('public')->delete($post->featured_image_path);
        }

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Đã xóa bài viết.');
    }

    private function payload(StorePostRequest $request, ?Post $post = null): array
    {
        $data = Arr::except($request->validated(), ['featured_image', 'remove_featured_image']);
        $data['author_user_id'] = $post?->author_user_id ?: $request->user()->getKey();
        if (($data['content_format'] ?? 'text') === 'html') {
            $data['content'] = app(PostHtml::class)->clean($data['content']);
        }

        if ($data['status'] === Post::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            if ($post?->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }

            $data['featured_image_path'] = $request->file('featured_image')->store('posts', 'public');
        }

        return $data;
    }
}
