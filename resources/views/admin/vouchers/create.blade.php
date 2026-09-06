@extends('layouts.admin')
@section('title', __('voucher.create'))
@section('content')<a class="admin-back-link" href="{{ route('admin.vouchers.index') }}">← Danh sách voucher</a>
    <div class="admin-section-heading voucher-create-heading">
        <div>
            <h1>{{ __('voucher.create') }}</h1>
            <p>Thiết lập ưu đãi và điều kiện sử dụng trước khi phát hành cho khách.</p>
        </div>
    </div>
    <form method="post" action="{{ route('admin.vouchers.store') }}" class="voucher-editor-form">@csrf
    @include('admin.vouchers._form')</form>@endsection
