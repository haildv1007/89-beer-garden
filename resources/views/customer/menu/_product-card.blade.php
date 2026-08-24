<article class="card h-100 shadow-sm">
    @if ($product->image_url)<img src="{{ $product->image_url }}" class="card-img-top product-image" alt="{{ $product->name }}" loading="lazy">@endif
    <div class="card-body d-flex flex-column">
        <small class="text-secondary">{{ $product->category->name }}</small>
        <h3 class="h5">{{ $product->name }}</h3>
        @if ($product->description)<p class="text-secondary">{{ Str::limit($product->description, 110) }}</p>@endif
        <div class="mt-auto d-flex align-items-center justify-content-between gap-2">
            <strong>{{ number_format($product->price, 0, ',', '.') }} ₫</strong>
            <span class="badge text-bg-{{ $product->is_available ? 'success' : 'secondary' }}">{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable') }}</span>
        </div>
        <a class="btn btn-outline-primary mt-3" href="{{ route('customer.products.show', $product) }}">{{ __('app.view_details') }}</a>
    </div>
</article>
