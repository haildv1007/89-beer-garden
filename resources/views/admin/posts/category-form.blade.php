@extends('layouts.admin')

@section('title', $category->exists ? 'Chỉnh sửa chuyên mục' : 'Thêm chuyên mục')

@section('content')
    <div class="post-editor-page post-category-editor">
        <header class="admin-section-heading">
            <h1>{{ $category->exists ? 'Chỉnh sửa chuyên mục' : 'Thêm chuyên mục' }}</h1>
            <a class="btn btn-outline-secondary" href="{{ route('admin.post-categories.index') }}">Danh sách chuyên mục</a>
        </header>
        <form class="post-editor-card" method="post"
            action="{{ $category->exists ? route('admin.post-categories.update', $category) : route('admin.post-categories.store') }}">
            @csrf
            @if ($category->exists)
                @method('put')
            @endif
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label" for="name">Tên chuyên mục <span class="text-danger">*</span></label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $category->name) }}" maxlength="100" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="slug">Đường dẫn</label>
                    <input class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                        value="{{ old('slug', $category->slug) }}" maxlength="40" placeholder="Tự tạo từ tên nếu để trống">
                    <div class="form-text">Dùng chữ không dấu và dấu gạch ngang, tối đa 40 ký tự.</div>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="sort_order">Thứ tự hiển thị <span class="text-danger">*</span></label>
                    <input class="form-control @error('sort_order') is-invalid @enderror" type="number" id="sort_order"
                        name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0"
                        max="999999" step="1" required>
                    <div class="form-text">Số nhỏ hiển thị trước. Cùng thứ tự sẽ giữ thứ tự tạo.</div>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="post-editor-actions">
                <a class="btn btn-outline-secondary" href="{{ route('admin.post-categories.index') }}">Hủy</a>
                <button class="btn btn-primary">{{ $category->exists ? 'Lưu thay đổi' : 'Tạo chuyên mục' }}</button>
            </div>
        </form>
    </div>
@endsection
