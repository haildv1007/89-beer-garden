@extends('layouts.admin')
@section('title', __('app.products.create'))
@section('content')
    <div class="product-edit-page">
    <div class="admin-section-heading product-create-heading">
        <div>
            <span class="admin-page-eyebrow">THỰC ĐƠN</span>
            <h1>Tạo sản phẩm mới</h1>
            <p>Thêm món mới vào thực đơn và thiết lập đầy đủ thông tin bán hàng.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.products.index') }}"><i class="ti ti-arrow-left"></i> Danh sách sản phẩm</a>
    </div>
    @if ($categories->isEmpty())
    <div class="alert alert-warning">{{ __('app.products.category_required') }}</div>@else<form method="post"
            action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="product-editor-form">
            @include('admin.products._form')</form>
    @endif
    </div>
@endsection
