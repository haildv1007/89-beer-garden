@extends('layouts.admin')
@section('title', __('app.products.create'))
@section('content')<a class="admin-back-link" href="{{ route('admin.products.index') }}">← Danh sách sản phẩm</a>
    <div class="admin-section-heading product-create-heading">
        <div>
            <h1>Tạo sản phẩm</h1>
            <p>Thêm món mới vào thực đơn và thiết lập đầy đủ thông tin bán hàng.</p>
        </div>
    </div>
    @if ($categories->isEmpty())
    <div class="alert alert-warning">{{ __('app.products.category_required') }}</div>@else<form method="post"
            action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="product-editor-form">
            @include('admin.products._form')</form>
    @endif
@endsection
