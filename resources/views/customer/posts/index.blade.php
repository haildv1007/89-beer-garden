@extends('layouts.customer')

@section('title', __('customer_ui.news_navigation') . ' — ' . __('app.name'))
@section('meta_description', 'Tin mới, sự kiện, ưu đãi và câu chuyện ẩm thực từ 89 Beer Garden.')
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
    @endphp

    @push('seo')
        <link rel="canonical"
            href="{{ route('customer.posts.index', array_filter(['category' => $category, 'page' => $posts->currentPage() > 1 ? $posts->currentPage() : null])) }}">
    @endpush

    <div class="news-page">
        <section class="news-masthead">
            <div class="container">
                <h1>{{ __('customer_ui.news_navigation') }}</h1>
                <p>Cập nhật món mới, sự kiện và những câu chuyện tạo nên không khí của Beer Garden.</p>
            </div>
        </section>

        <nav class="news-category-bar" aria-label="Chuyên mục tin tức">
            <div class="container">
                <a @class(['active' => !$category]) href="{{ route('customer.posts.index') }}">Tất cả</a>
                @foreach ($categoryLabels as $value => $label)
                    <a @class(['active' => $category === $value])
                        href="{{ route('customer.posts.index', ['category' => $value]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </nav>

        <section class="news-listing">
            <div class="container">
                @if ($featuredPost)
                    <article class="news-featured">
                        <a class="news-featured-media" href="{{ route('customer.posts.show', $featuredPost) }}">
                            <img src="{{ $featuredPost->featured_image_url ?: $fallbackImages[$featuredPost->category] }}"
                                alt="{{ $featuredPost->title }}">
                        </a>
                        <div class="news-featured-copy">
                            @include('customer.posts._meta', ['entry' => $featuredPost])
                            <h2><a href="{{ route('customer.posts.show', $featuredPost) }}">{{ $featuredPost->title }}</a>
                            </h2>
                            <p>{{ $featuredPost->excerpt }}</p>
                            <a class="news-read-link" href="{{ route('customer.posts.show', $featuredPost) }}">Đọc bài
                                viết</a>
                        </div>
                    </article>
                @endif

                @if ($posts->isNotEmpty())
                    <div class="news-grid">
                        @foreach ($posts as $post)
                            <article class="news-card">
                                <a class="news-card-media" href="{{ route('customer.posts.show', $post) }}">
                                    <img src="{{ $post->featured_image_url ?: $fallbackImages[$post->category] }}"
                                        alt="{{ $post->title }}" loading="lazy">
                                </a>
                                <div class="news-card-copy">
                                    @include('customer.posts._meta', ['entry' => $post])
                                    <h2><a href="{{ route('customer.posts.show', $post) }}">{{ $post->title }}</a></h2>
                                    <p>{{ $post->excerpt }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="news-pagination">{{ $posts->links() }}</div>
                @elseif (!$featuredPost)
                    <div class="news-empty">
                        <span aria-hidden="true">▱</span>
                        <h2>Chưa có bài viết</h2>
                        <p>Nội dung mới đang được chuẩn bị. Bạn quay lại sau nhé.</p>
                        <a class="btn btn-primary" href="{{ route('customer.menu.index') }}">Xem thực đơn</a>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
