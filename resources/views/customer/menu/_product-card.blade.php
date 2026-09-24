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
    $translatedProductDescription = trim(strip_tags($translatedProductDescription ?? ''));
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
<article @class(['product-card', 'product-card--unavailable' => ! $product->is_available]) data-product-id="{{ $product->id }}" data-product-name="{{ $translatedProductName }}"
    data-product-category="{{ $translatedCategory }}"
    data-product-description="{{ $translatedProductDescription ?: '—' }}"
    data-product-price="{{ $product->display_price }}"
    data-product-image="{{ $product->primary_image_url ?: $fallbackImage }}"
    data-product-media="{{ $quickMedia->toJson() }}" data-product-available="{{ $product->is_available ? '1' : '0' }}"
    data-product-tags="{{ $product->tags->map(fn($tag) => ['name' => $tag->name, 'icon' => $tag->icon_class, 'color' => $tag->icon_color ?: '#17633f', 'background' => $tag->background_color ?: '#eaf7ef'])->values()->toJson() }}"
    data-product-variants="{{ $product->variants->where('is_available', true)->map(fn($variant) => ['id' => $variant->id, 'name' => $variant->name, 'price' => $variant->price])->values()->toJson() }}"
    data-product-status="{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable') }}"
    data-product-url="{{ route('customer.products.show', $product) }}"><a class="product-media"
        href="{{ route('customer.products.show', $product) }}" data-product-modal
        aria-label="{{ __('customer_ui.view_dish', ['name' => $translatedProductName]) }}"><img
            src="{{ $product->primary_image_url ?: $fallbackImage }}"
            alt="{{ $product->primary_image_url ? $translatedProductName : __('customer_ui.image_fallback', ['category' => $translatedCategory]) }}"
            loading="lazy">
        @if ($product->is_available && $product->tags->isNotEmpty())
            <div class="product-tags product-tags--media" aria-label="Tag món ăn">
                @foreach ($product->tags as $tag)
                    <span class="product-tag product-tag--{{ $tag->slug }}"
                        style="--tag-color: {{ $tag->icon_color ?: '#17633f' }}; --tag-bg: {{ $tag->background_color ?: '#eaf7ef' }}">@if($tag->icon_class)<i class="{{ $tag->icon_class }}" aria-hidden="true"></i>@endif{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif
        @unless ($product->is_available)
            <span class="availability availability--no">{{ __('app.products.unavailable') }}</span>
        @endunless
    </a>
    <div class="product-card-body">
        <div class="product-card-heading">
            <div class="product-card-titles"><span class="product-category">{{ $translatedCategory }}</span>
                <h3 class="product-name"><a href="{{ route('customer.products.show', $product) }}"
                        data-product-modal>{{ $translatedProductName }}</a></h3>
            </div>
        </div>
        <p class="product-description">{{ $translatedProductDescription ?: '—' }}</p>
        <div class="product-card-footer"><span class="product-price">{{ $product->display_price }}</span>
            <form class="product-quick-add js-submit-once" data-add-to-cart method="post"
                action="{{ route('customer.cart.items.store') }}">
                @csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="quantity" value="1">
                <button type="{{ $product->variants->where('is_available', true)->isNotEmpty() ? 'button' : 'submit' }}"
                    @if($product->variants->where('is_available', true)->isNotEmpty()) data-product-modal @endif @disabled(! $product->is_available)
                    aria-label="{{ $product->is_available ? __('customer_order.add_to_cart') . ': ' . $translatedProductName : __('app.products.unavailable') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg>
                </button>
            </form>
        </div>
    </div>
</article>
