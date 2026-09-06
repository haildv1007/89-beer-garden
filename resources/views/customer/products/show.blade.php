@extends('layouts.customer')
@section('title', $translatedName . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish($translatedShortDescription
    ?: strip_tags($translatedDescription)), 155, ''))
@section('content')
    @php
        $categoryKey = strtolower($product->category->slug ?: $product->slug);
        $fallbackImage =
            $product->image_url ?:
            (str_contains($categoryKey, 'bia') ||
            str_contains($categoryKey, 'drink') ||
            str_contains($categoryKey, 'nuoc')
                ? asset('images/brand/beer-cheers.jpg')
                : asset('images/brand/grilled-feast.jpg'));
        $firstMedia = $product->media->first();
    @endphp

    <div class="product-detail-page">
        <nav class="product-breadcrumb" aria-label="{{ __('customer_ui.product_detail') }}">
            <a href="{{ route('customer.menu.index') }}">{{ __('app.menu.title') }}</a><span aria-hidden="true">/</span>
            <a
                href="{{ route('customer.menu.index', ['category' => $product->category->slug]) }}">{{ $translatedCategoryName }}</a><span
                aria-hidden="true">/</span>
            <span aria-current="page">{{ $translatedName }}</span>
        </nav>

        <article class="product-detail-hero">
            <section class="product-detail-gallery" data-detail-gallery aria-label="{{ __('customer_ui.product_media') }}">
                <figure class="product-detail-stage">
                    @if ($firstMedia?->media_type === 'video')
                        <img data-detail-image src="" alt="{{ $translatedName }}" hidden draggable="false">
                        <video data-detail-video src="{{ $firstMedia->url }}" controls preload="metadata"></video>
                    @else
                        <img data-detail-image src="{{ $firstMedia?->url ?: $fallbackImage }}" alt="{{ $translatedName }}"
                            draggable="false">
                        <video data-detail-video controls preload="metadata" hidden></video>
                    @endif
                    @if ($product->media->count() > 1)
                        <span class="product-detail-media-count" data-detail-count>1/{{ $product->media->count() }}</span>
                    @endif
                </figure>
                @if ($product->media->count() > 1)
                    <div class="product-detail-thumbnails" role="list">
                        @foreach ($product->media as $media)
                            <button type="button" @class(['product-detail-thumbnail', 'is-active' => $loop->first]) data-detail-media
                                data-media-type="{{ $media->media_type }}" data-media-url="{{ $media->url }}"
                                aria-label="{{ $translatedName }} {{ $loop->iteration }}">
                                @if ($media->media_type === 'video')
                                <span aria-hidden="true">▶</span>@else<img src="{{ $media->url }}" alt=""
                                        loading="lazy">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="product-detail-summary">
                <span class="product-category">{{ $translatedCategoryName }}</span>
                <h1>{{ $translatedName }}</h1>
                @if ($translatedShortDescription)
                    <p class="product-detail-intro">{{ $translatedShortDescription }}</p>
                @endif
                <div class="product-detail-price-row">
                    <strong>{{ number_format($product->price, 0, ',', '.') }} ₫</strong>
                    <span
                        @class([
                            'product-detail-availability',
                            'is-unavailable' => !$product->is_available,
                        ])>{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable') }}</span>
                </div>

                @if ($product->is_available)
                    <form class="product-detail-order js-submit-once" data-add-to-cart method="post"
                        action="{{ route('customer.cart.items.store') }}">
                        @csrf<input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div><label for="quantity">{{ __('customer_order.quantity') }}</label>
                            <div class="quantity-stepper"><button type="button" data-detail-quantity-minus
                                    aria-label="{{ __('customer_ui.quantity_decrease') }}">−</button><input id="quantity"
                                    name="quantity" type="number" min="1" max="1000" value="1"
                                    required><button type="button" data-detail-quantity-plus
                                    aria-label="{{ __('customer_ui.quantity_increase') }}">+</button></div>
                        </div>
                        <button class="btn btn-reservation">{{ __('customer_order.add_to_cart') }}</button>
                    </form>
                @else
                    <div class="alert alert-danger mt-4">{{ __('app.products.unavailable_feedback') }}</div>
                @endif
            </section>
        </article>

        @if ($translatedDescription)
            <section class="product-detail-copy" aria-labelledby="product-description-heading">
                <h2 id="product-description-heading">{{ __('customer_ui.product_story_eyebrow') }}</h2>
                <div class="product-detail-prose">{!! nl2br(e($translatedDescription)) !!}</div>
            </section>
        @endif

        @if ($relatedProducts->isNotEmpty())
            <section class="product-related" aria-labelledby="related-products-heading">
                <div class="product-related-heading">
                    <h2 id="related-products-heading">{{ __('customer_ui.related_eyebrow') }}</h2><a
                        href="{{ route('customer.menu.index', ['category' => $product->category->slug]) }}">{{ __('customer_ui.related_view_all') }}
                        <span aria-hidden="true">→</span></a>
                </div>
                <div class="product-related-grid">
                    @foreach ($relatedProducts as $related)
                        @php
                            $relatedName = $relatedTranslations->get(
                                $related::class . ':' . $related->id . ':name',
                                $related->name,
                            );
                            $relatedShort =
                                $relatedTranslations->get(
                                    $related::class . ':' . $related->id . ':short_description',
                                    $related->short_description,
                                ) ?:
                                $relatedTranslations->get(
                                    $related::class . ':' . $related->id . ':description',
                                    $related->description,
                                );
                            $relatedCategory = $relatedTranslations->get(
                                get_class($related->category) . ':' . $related->category->id . ':name',
                                $related->category->name,
                            );
                            $relatedKey = strtolower($related->category->slug ?: $related->slug);
                            $relatedImage =
                                $related->primary_image_url ?:
                                (str_contains($relatedKey, 'bia') ||
                                str_contains($relatedKey, 'drink') ||
                                str_contains($relatedKey, 'nuoc')
                                    ? asset('images/brand/beer-cheers.jpg')
                                    : asset('images/brand/grilled-feast.jpg'));
                        @endphp
                        <article class="product-related-card"><a class="product-related-media"
                                href="{{ route('customer.products.show', $related) }}"><img src="{{ $relatedImage }}"
                                    alt="{{ $relatedName }}"
                                    loading="lazy"><span>{{ $related->is_available ? __('app.products.available') : __('app.products.unavailable') }}</span></a>
                            <div><small>{{ $relatedCategory }}</small>
                                <h3><a href="{{ route('customer.products.show', $related) }}">{{ $relatedName }}</a></h3>
                                <p>{{ \Illuminate\Support\Str::limit($relatedShort, 90) }}</p>
                                <strong>{{ number_format($related->price, 0, ',', '.') }} ₫</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
