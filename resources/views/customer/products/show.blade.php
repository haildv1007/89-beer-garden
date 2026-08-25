@extends('layouts.customer')
@section('title', $translatedName.' — '.__('app.name'))
@section('content')
    <a href="{{ route('customer.menu.index', ['category' => $product->category->slug]) }}">&larr; {{ __('app.back_to_menu') }}</a>
    <div class="row g-4 mt-1">
        <div class="col-12 col-md-6">@if ($product->image_url)<img class="img-fluid rounded product-detail-image" src="{{ $product->image_url }}" alt="{{ $product->name }}">@else<div class="bg-light rounded p-5 text-center text-secondary">{{ __('app.products.no_image') }}</div>@endif</div>
        <div class="col-12 col-md-6"><span class="text-secondary">{{ $translatedCategoryName }}</span><h1>{{ $translatedName }}</h1><p>{{ $translatedDescription }}</p><p class="fs-3 fw-bold">{{ number_format($product->price, 0, ',', '.') }} ₫</p><div class="alert alert-{{ $product->is_available ? 'success' : 'warning' }}">{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable_feedback') }}</div>
            @if ($customerOrderingAvailable && $product->is_available)
                <form method="post" action="{{ route('customer.cart.items.store') }}">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><div class="mb-2"><label class="form-label" for="quantity">{{ __('customer_order.quantity') }}</label><input class="form-control" id="quantity" name="quantity" type="number" min="1" max="1000" value="1" required></div><div class="mb-2"><label class="form-label" for="note">{{ __('customer_order.item_note') }}</label><input class="form-control" id="note" name="note"></div><button class="btn btn-primary">{{ __('customer_order.add_to_cart') }}</button></form>
            @elseif (! $customerOrderingAvailable)
                <div class="alert alert-secondary">{{ __('customer_order.context_required') }}</div>
            @endif
        </div>
    </div>
@endsection
