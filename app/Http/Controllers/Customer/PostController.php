<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $category = in_array($request->query('category'), array_keys(PostCategory::labels()), true)
            ? $request->query('category')
            : null;
        $query = Post::query()->published()->when(
            $category,
            fn ($query) => $query->where('category', $category),
        );
        $featuredPost = (clone $query)
            ->where('is_featured', true)
            ->latest('published_at')
            ->first() ?? (clone $query)->latest('published_at')->first();
        $posts = $query
            ->when($featuredPost, fn ($query) => $query->whereKeyNot($featuredPost->getKey()))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('customer.posts.index', compact('posts', 'featuredPost', 'category'));
    }

    public function show(Post $post): View
    {
        abort_unless(
            $post->status === Post::STATUS_PUBLISHED &&
            $post->published_at?->isPast(),
            404,
        );

        $relatedPosts = Post::query()
            ->published()
            ->whereKeyNot($post->getKey())
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$post->category])
            ->latest('published_at')
            ->limit(6)
            ->get();

        $latestPosts = Post::query()->published()->whereKeyNot($post->getKey())
            ->latest('published_at')->limit(4)->get();

        return view('customer.posts.show', compact('post', 'relatedPosts', 'latestPosts'));
    }
}
