<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request, DynamicTranslationResolver $resolver): View
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

        $postEntities = (new EloquentCollection([$featuredPost]))->filter()->concat($posts->getCollection());
        $categoryModels = PostCategory::query()->orderBy('sort_order')->orderBy('id')->get();
        $translations = $resolver->batch($postEntities->concat($categoryModels), fields: ['title', 'excerpt', 'name']);
        $this->applyPostTranslations($postEntities, $translations);
        $categoryLabels = $categoryModels->mapWithKeys(fn (PostCategory $item) => [
            $item->slug => $translations->get($item::class.':'.$item->id.':name', $item->name),
        ])->all();

        return view('customer.posts.index', compact('posts', 'featuredPost', 'category', 'categoryLabels'));
    }

    public function show(Post $post, DynamicTranslationResolver $resolver): View
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

        $postEntities = (new EloquentCollection([$post]))->concat($relatedPosts)->concat($latestPosts)
            ->unique(fn (Post $item) => $item->getKey())->values();
        $categoryModels = PostCategory::query()->orderBy('sort_order')->orderBy('id')->get();
        $translations = $resolver->batch(new EloquentCollection([$post]), fields: ['title', 'excerpt', 'content']);
        $translations = $translations->merge(
            $resolver->batch($postEntities->skip(1)->concat($categoryModels), fields: ['title', 'name']),
        );
        $this->applyPostTranslations($postEntities, $translations);
        $categoryLabels = $categoryModels->mapWithKeys(fn (PostCategory $item) => [
            $item->slug => $translations->get($item::class.':'.$item->id.':name', $item->name),
        ])->all();

        return view('customer.posts.show', compact('post', 'relatedPosts', 'latestPosts', 'categoryLabels'));
    }

    private function applyPostTranslations($posts, $translations): void
    {
        foreach ($posts as $post) {
            foreach (['title', 'excerpt', 'content'] as $field) {
                $post->setAttribute($field, $translations->get($post::class.':'.$post->id.':'.$field, $post->{$field}));
            }
        }
    }
}
