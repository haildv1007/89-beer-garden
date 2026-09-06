@extends('layouts.admin')
@section('title', 'Chỉnh sửa ' . $customer->name)
@section('content')
    <header class="admin-page-header">
        <div><span class="admin-page-eyebrow">Hồ sơ khách hàng</span>
            <h1>Chỉnh sửa khách hàng</h1>
            <p>Số điện thoại được dùng làm định danh và đăng nhập. Email chỉ dùng để liên hệ hoặc marketing.</p>
        </div><a class="btn btn-outline-secondary" href="{{ route('admin.customers.show', $customer) }}">Quay lại hồ sơ</a>
    </header>
    <form method="post" action="{{ route('admin.customers.update', $customer) }}" class="admin-form-section">@csrf
        @method('put')
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="name">Tên hiển thị *</label><input
                    class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                    value="{{ old('name', $customer->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6"><label class="form-label" for="phone">Số điện thoại đăng nhập *</label><input
                    class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                    value="{{ old('phone', $customer->phone) }}" required inputmode="tel">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12"><label class="form-label" for="email">Email liên hệ/marketing <small
                        class="text-secondary">(không bắt buộc)</small></label><input
                    class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email"
                    value="{{ old('email', $customer->email ?? $customer->user?->email) }}">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Lưu thay đổi</button><a
                class="btn btn-outline-secondary" href="{{ route('admin.customers.show', $customer) }}">Hủy</a></div>
    </form>
@endsection
