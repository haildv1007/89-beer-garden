@extends('layouts.customer')

@section('title', $post->title . ' — ' . __('app.name'))
@section('meta_description', $post->excerpt)
@section('page-layout', 'flush')

@section('content')
    @php
        $categoryLabels = \App\Models\PostCategory::labels();
        $fallbackImages = [
            'food' => asset('images/brand/grilled-feast.jpg'),
            'event' => asset('images/brand/atmosphere-evening.jpg'),
            'promotion' => asset('images/beer-garden-hero.png'),
            'story' => asset('images/brand/beer-cheers.jpg'),
        ];
        $fallbackImages += array_fill_keys(array_keys($categoryLabels), asset('images/brand/atmosphere-evening.jpg'));
        $paragraphs = preg_split('/\R{2,}/u', trim($post->content)) ?: [];
    @endphp

    @push('seo')
        <link rel="canonical" href="{{ route('customer.posts.show', $post) }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $post->title }}">
        <meta property="og:description" content="{{ $post->excerpt }}">
        <meta property="og:url" content="{{ route('customer.posts.show', $post) }}">
        <meta property="og:image" content="{{ url($post->featured_image_url ?: $fallbackImages[$post->category]) }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $post->title }}">
        <meta name="twitter:description" content="{{ $post->excerpt }}">
        <meta name="twitter:image" content="{{ url($post->featured_image_url ?: $fallbackImages[$post->category]) }}">
        <meta property="article:published_time" content="{{ $post->published_at->toAtomString() }}">
        @php
            $articleSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'description' => $post->excerpt,
                'image' => url($post->featured_image_url ?: $fallbackImages[$post->category]),
                'datePublished' => $post->published_at->toAtomString(),
                'dateModified' => $post->updated_at->toAtomString(),
                'mainEntityOfPage' => route('customer.posts.show', $post),
                'publisher' => ['@type' => 'Organization', 'name' => '89 Beer Garden', 'url' => url('/')],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($articleSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endpush

    <div class="container article-layout">
        <article class="article-page">
            <header class="article-header">
                <div class="article-header-inner">
                    <a class="article-back" href="{{ route('customer.posts.index') }}">{{ __('customer_ui.news_navigation') }}</a>
                    <h1>{{ $post->title }}</h1>
                    <p>{{ $post->excerpt }}</p>
                    @include('customer.posts._meta', ['entry' => $post])
                </div>
            </header>

            <div class="article-container">
                <figure class="article-cover">
                    <img src="{{ $post->featured_image_url ?: $fallbackImages[$post->category] }}"
                        alt="{{ $post->title }}">
                </figure>
                <div class="article-body">
                    {!! $post->content_html !!}
                </div>
            </div>
        </article>
        @if ($latestPosts->isNotEmpty())
            <aside class="article-sidebar" aria-labelledby="latest-news-title">
                <h2 id="latest-news-title">Tin mới</h2>
                @foreach ($latestPosts as $latestPost)
                    <article class="article-sidebar-item">
                        <a href="{{ route('customer.posts.show', $latestPost) }}">
                            <img src="{{ $latestPost->featured_image_url ?: $fallbackImages[$latestPost->category] }}"
                                alt="" loading="lazy" width="96" height="72">
                            <h3>{{ $latestPost->title }}</h3>
                        </a>
                        @include('customer.posts._meta', ['entry' => $latestPost])
                    </article>
                @endforeach
            </aside>
        @endif
    </div>

    @if ($relatedPosts->isNotEmpty())
        <section class="related-news">
            <div class="container">
                <header>
                    <h2>Bài viết khác</h2>
                </header>
                <div class="related-news-grid">
                    @foreach ($relatedPosts as $relatedPost)
                        <article>
                            <a href="{{ route('customer.posts.show', $relatedPost) }}">
                                <img src="{{ $relatedPost->featured_image_url ?: $fallbackImages[$relatedPost->category] }}"
                                    alt="{{ $relatedPost->title }}" loading="lazy">
                                <h3>{{ $relatedPost->title }}</h3>
                            </a>
                            @include('customer.posts._meta', ['entry' => $relatedPost])
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
