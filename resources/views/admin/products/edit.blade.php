@extends('layouts.admin')
@section('title', __('app.products.edit'))
@section('content')
    <div class="product-edit-page">
        <a class="admin-back-link" href="{{ route('admin.products.index') }}">← Danh sách sản phẩm</a>
        <header class="product-editor-header"><img
                src="{{ $product->primary_image_url ?: asset('images/brand/quan-89-logo.png') }}" alt="{{ $product->name }}">
            <div><span>CHỈNH SỬA SẢN PHẨM</span>
                <h1>{{ $product->name }}</h1>
                <p>{{ $product->category->name }} · {{ $product->slug }}</p>
            </div>
        </header>
        @can('product.update-price')
            <section class="product-price-editor">
                <div><span>GIÁ BÁN HIỆN TẠI</span><strong>{{ number_format($product->price, 0, ',', '.') }} ₫</strong>
                    <p>Giá mới chỉ áp dụng cho lượt gọi món mới, không ảnh hưởng hóa đơn cũ.</p>
                </div>
                <form method="post" action="{{ route('admin.products.price.update', $product) }}">@csrf @method('patch')<label
                        for="price">Giá mới (VNĐ)</label>
                    <div><input class="form-control @error('price') is-invalid @enderror" id="price" name="price"
                            type="number" min="0" value="{{ old('price', $product->price) }}" required><button
                            class="btn btn-primary">Cập nhật giá</button></div>
                    @error('price')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </form>
            </section>
        @endcan
        <form method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data"
            class="product-editor-form">@include('admin.products._form')</form>
    </div>
@endsection
