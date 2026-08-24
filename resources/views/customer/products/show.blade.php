@extends('layouts.customer')
@section('title', $product->name.' — '.__('app.name'))
@section('content')
    <a href="{{ route('customer.menu.index', ['category' => $product->category->slug]) }}">&larr; {{ __('app.back_to_menu') }}</a>
    <div class="row g-4 mt-1">
        <div class="col-12 col-md-6">@if ($product->image_url)<img class="img-fluid rounded product-detail-image" src="{{ $product->image_url }}" alt="{{ $product->name }}">@else<div class="bg-light rounded p-5 text-center text-secondary">{{ __('app.products.no_image') }}</div>@endif</div>
        <div class="col-12 col-md-6"><span class="text-secondary">{{ $product->category->name }}</span><h1>{{ $product->name }}</h1><p>{{ $product->description }}</p><p class="fs-3 fw-bold">{{ number_format($product->price, 0, ',', '.') }} ₫</p><div class="alert alert-{{ $product->is_available ? 'success' : 'warning' }}">{{ $product->is_available ? __('app.products.available') : __('app.products.unavailable_feedback') }}</div><button class="btn btn-secondary" disabled>{{ __('app.products.cart_later') }}</button></div>
    </div>
@endsection
