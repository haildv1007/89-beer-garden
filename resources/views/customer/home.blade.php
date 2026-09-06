@extends('layouts.customer')
@section('title', __('app.name') . ' — ' . __('customer_ui.brand_tagline'))
@section('page-layout', 'flush')
@section('content')
    @php
        $homeSettings = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->publicSiteSettings();
        $homeBannerPath = $homeSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_BANNER] ?? null;
        $homeBannerUrl = $homeBannerPath ? Storage::disk('public')->url($homeBannerPath) : null;
    @endphp
    <section class="hero" @if ($homeBannerUrl) style="--hero-background-image: url('{{ $homeBannerUrl }}')" @endif>
        <div class="container">
            <div class="hero-content"><span class="eyebrow">{{ __('customer_ui.hero_eyebrow') }}</span>
                <h1 class="display-title">{{ __('customer_ui.hero_heading') }}</h1>
                <p class="visually-hidden">{{ __('app.home.heading') }}</p>
                <p class="hero-copy">{{ __('customer_ui.hero_copy') }}</p>
                <div class="hero-actions"><a class="btn btn-primary btn-reservation btn-lg"
                        href="{{ route('customer.reservations.create') }}">{{ __('reservation.customer.make') }}</a><a
                        class="btn btn-light-outline btn-lg"
                        href="{{ route('customer.menu.index') }}">{{ __('app.home.view_menu') }}</a></div>
            </div>
        </div>
        <a class="hero-scroll" href="#cau-chuyen"><span>{{ __('customer_ui.hero_scroll') }}</span><span
                aria-hidden="true">↓</span></a>
    </section>

    <section class="brand-story" id="cau-chuyen" aria-labelledby="story-heading">
        <div class="container">
            <figure class="brand-story-visual">
                <img src="{{ asset('images/brand/beer-cheers.jpg') }}" alt="{{ __('customer_ui.people_image_alt') }}"
                    loading="lazy">
            </figure>
            <div class="brand-story-body">
                <span class="eyebrow">{{ __('customer_ui.story_eyebrow') }}</span>
                <h2 id="story-heading">{{ __('customer_ui.story_heading') }}</h2>
                <p>{{ __('customer_ui.story_copy') }}</p>
                <a class="btn home-outline-button" href="#khong-gian">{{ __('customer_ui.story_link') }}</a>
            </div>
        </div>
    </section>

    @php
        $featuredCategoryName = $featuredCategory
            ? ($dynamicTranslations ?? collect())->get(
                $featuredCategory::class . ':' . $featuredCategory->id . ':name',
                $featuredCategory->name,
            )
            : __('customer_ui.signature_eyebrow');
        $signatureDesktopColumns = min(4, max(1, (int) ceil($products->count() / 2)));
    @endphp
    <section class="signature-showcase" id="mon-noi-bat" aria-labelledby="signature-heading">
        <div class="container">
            <header class="signature-heading">
                <div>
                    <span class="eyebrow">{{ $featuredCategoryName }}</span>
                    <h2 id="signature-heading">{{ __('customer_ui.signature_heading') }}</h2>
                </div>
                <div class="signature-heading-copy">
                    <p>{{ __('customer_ui.signature_copy') }}</p>
                    <a class="text-link" href="{{ route('customer.menu.index') }}">{{ __('customer_ui.view_full_menu') }}</a>
                </div>
            </header>

            @if ($products->isEmpty())
                <div class="empty-state">
                    <p>{{ __('app.menu.empty') }}</p>
                </div>
            @else
                <div class="signature-carousel" data-carousel>
                    <div class="signature-carousel-controls" aria-label="{{ __('customer_ui.signature_controls') }}">
                        <button type="button" data-carousel-prev
                            aria-label="{{ __('customer_ui.signature_previous') }}"><span
                                aria-hidden="true">←</span></button>
                        <button type="button" data-carousel-next aria-label="{{ __('customer_ui.signature_next') }}"><span
                                aria-hidden="true">→</span></button>
                    </div>
                    <div class="signature-track signature-track--{{ $signatureDesktopColumns }}" data-carousel-track
                        tabindex="0">
                        @foreach ($products as $product)
                            @php
                                $translatedProductName = ($dynamicTranslations ?? collect())->get(
                                    $product::class . ':' . $product->id . ':name',
                                    $product->name,
                                );
                                $translatedProductDescription =
                                    ($dynamicTranslations ?? collect())->get(
                                        $product::class . ':' . $product->id . ':short_description',
                                        $product->short_description,
                                    ) ?:
                                    ($dynamicTranslations ?? collect())->get(
                                        $product::class . ':' . $product->id . ':description',
                                        $product->description,
                                    );
                                $translatedCategory = ($dynamicTranslations ?? collect())->get(
                                    get_class($product->category) . ':' . $product->category->id . ':name',
                                    $product->category->name,
                                );
                                $categoryKey = strtolower($product->category->slug ?: $product->slug);
                                $foodFallbacks = [
                                    asset('images/brand/grilled-feast.jpg'),
                                    asset('images/beer-garden-hero.png'),
                                    asset('images/beer-garden-hero.png'),
                                    asset('images/brand/grilled-feast.jpg'),
                                ];
                                $fallbackImage =
                                    str_contains($categoryKey, 'bia') ||
                                    str_contains($categoryKey, 'drink') ||
                                    str_contains($categoryKey, 'nuoc')
                                        ? asset('images/brand/beer-cheers.jpg')
                                        : $foodFallbacks[$loop->index % count($foodFallbacks)];
                            @endphp
                            <article class="signature-dish">
                                <a class="signature-dish-media" href="{{ route('customer.products.show', $product) }}">
                                    <img src="{{ $product->primary_image_url ?: $fallbackImage }}"
                                        alt="{{ $product->primary_image_url ? $translatedProductName : __('customer_ui.image_fallback', ['category' => $translatedCategory]) }}"
                                        loading="lazy">
                                </a>
                                <div class="signature-dish-meta">
                                    <span>{{ $translatedCategory }}</span>
                                    <h3><a
                                            href="{{ route('customer.products.show', $product) }}">{{ $translatedProductName }}</a>
                                    </h3>
                                    <p>{{ $translatedProductDescription ?: '—' }}</p>
                                    <strong>{{ number_format($product->price, 0, ',', '.') }} ₫</strong>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="atmosphere-feature" id="khong-gian" aria-labelledby="atmosphere-heading"><img
            src="{{ asset('images/brand/atmosphere-evening.jpg') }}" alt="{{ __('customer_ui.atmosphere_image_alt') }}"
            loading="lazy">
        <div class="container">
            <div class="atmosphere-message"><span class="eyebrow">{{ __('customer_ui.atmosphere_eyebrow') }}</span>
                <h2 id="atmosphere-heading">{{ __('customer_ui.atmosphere_heading') }}</h2>
                <p>{{ __('customer_ui.atmosphere_copy') }}</p>
            </div>
        </div>
    </section>

    <section class="brand-pillars" aria-labelledby="pillars-heading">
        <div class="container">
            <header><span class="eyebrow">{{ __('customer_ui.pillars_label') }}</span>
                <h2 id="pillars-heading">{{ __('customer_ui.pillars_heading') }}</h2>
                <p>{{ __('customer_ui.pillars_copy') }}</p>
            </header>
            <div class="brand-pillars-list">
                @foreach (__('customer_ui.pillars') as $index => $pillar)
                    <article><span>0{{ $index + 1 }}</span>
                        <h3>{{ $pillar['title'] }}</h3>
                        <p>{{ $pillar['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($latestPosts->isNotEmpty())
        @php
            $newsCategoryLabels = [
                'food' => 'Ẩm thực',
                'event' => 'Sự kiện',
                'promotion' => 'Ưu đãi',
                'story' => 'Câu chuyện',
            ];
            $newsFallbackImages = [
                'food' => asset('images/brand/grilled-feast.jpg'),
                'event' => asset('images/brand/atmosphere-evening.jpg'),
                'promotion' => asset('images/beer-garden-hero.png'),
                'story' => asset('images/brand/beer-cheers.jpg'),
            ];
        @endphp
        <section class="home-news" id="tin-tuc" aria-labelledby="news-heading">
            <div class="container">
                <header class="home-news-heading">
                    <div><span class="eyebrow">{{ __('customer_ui.news_eyebrow') }}</span>
                        <h2 id="news-heading">{{ __('customer_ui.news_heading') }}</h2>
                    </div>
                    <div>
                        <p>{{ __('customer_ui.news_copy') }}</p>
                        <a class="btn home-outline-button" href="{{ route('customer.posts.index') }}">{{ __('customer_ui.news_view_all') }}</a>
                    </div>
                </header>
                <div class="home-news-grid">
                    @foreach ($latestPosts as $post)
                        <article @class(['news-story', 'news-story--lead' => $loop->first])>
                            <a class="news-story-media" href="{{ route('customer.posts.show', $post) }}">
                                <img src="{{ $post->featured_image_url ?: ($newsFallbackImages[$post->category] ?? asset('images/brand/atmosphere-evening.jpg')) }}"
                                    alt="{{ $post->title }}" loading="lazy">
                            </a>
                            <div class="news-story-content">
                                <span>{{ \App\Models\PostCategory::labels()[$post->category] ?? $post->category }} ·
                                    <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('d/m/Y') }}</time></span>
                                <h3><a href="{{ route('customer.posts.show', $post) }}">{{ $post->title }}</a></h3>
                                <p>{{ $post->excerpt }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="reservation-cta">
        <div class="container">
            <div><span class="eyebrow">{{ __('customer_ui.reservation_eyebrow_short') }}</span>
                <h2>{{ __('customer_ui.footer_reservation') }}</h2>
                <p>{{ __('customer_ui.footer_reservation_copy') }}</p>
            </div><a class="btn btn-primary btn-reservation btn-lg"
                href="{{ route('customer.reservations.create') }}">{{ __('reservation.customer.make') }}</a>
        </div>
    </section>
@endsection
