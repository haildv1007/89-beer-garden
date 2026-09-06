@php
    $translatedProductName = ($dynamicTranslations ?? collect())->get(
        $product::class . ':' . $product->id . ':name',
        $product->name,
    );
    $translatedCategory = ($dynamicTranslations ?? collect())->get(
        get_class($product->category) . ':' . $product->category->id . ':name',
        $product->category->name,
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
    $categoryKey = strtolower($product->category->slug ?: $product->slug);
    $fallbackImage =
        str_contains($categoryKey, 'bia') || str_contains($categoryKey, 'drink') || str_contains($categoryKey, 'nuoc')
            ? asset('images/brand/beer-cheers.jpg')
            : asset('images/brand/grilled-feast.jpg');
    $quickMedia = $product->media->map(fn($media) => ['type' => $media->media_type, 'url' => $media->url])->values();
    if ($quickMedia->isEmpty()) {
        $quickMedia->push(['type' => 'image', 'url' => $product->primary_image_url ?: $fallbackImage]);
    }
@endphp
<article class="product-card" data-product-id="{{ $product->id }}" data-product-name="{{ $translatedProductName }}"
    data-product-category="{{ $translatedCategory }}"
    data-product-description="{{ $translatedProductDescription ?: '—' }}"
    data-product-price="{{ number_format($product->price, 0, ',', '.') }} ₫"
    data-product-image="{{ $product->primary_image_url ?: $fallbackImage }}"
    data-product-media="{{ $quickMedia->toJson() }}" data-product-available="{{ $product->is_available ? '1' : '0' }}"
    data-product-status="{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable') }}"
    data-product-url="{{ route('customer.products.show', $product) }}"><a class="product-media"
        href="{{ route('customer.products.show', $product) }}" data-product-modal
        aria-label="{{ __('customer_ui.view_dish', ['name' => $translatedProductName]) }}"><img
            src="{{ $product->primary_image_url ?: $fallbackImage }}"
            alt="{{ $product->primary_image_url ? $translatedProductName : __('customer_ui.image_fallback', ['category' => $translatedCategory]) }}"
            loading="lazy"><span
            class="availability availability--{{ $product->is_available ? 'yes' : 'no' }}">{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable') }}</span></a>
    <div class="product-card-body"><span class="product-category">{{ $translatedCategory }}</span>
        <h3 class="product-name"><a href="{{ route('customer.products.show', $product) }}"
                data-product-modal>{{ $translatedProductName }}</a></h3>
        <p class="product-description">{{ $translatedProductDescription ?: '—' }}</p>
        <div class="product-card-footer"><span class="product-price">{{ number_format($product->price, 0, ',', '.') }}
                ₫</span>
            <form class="product-quick-add js-submit-once" data-add-to-cart method="post"
                action="{{ route('customer.cart.items.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" @disabled(! $product->is_available)
                    aria-label="{{ $product->is_available ? __('customer_order.add_to_cart') . ': ' . $translatedProductName : __('app.products.unavailable') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg>
                </button>
            </form>
        </div>
    </div>
</article>
