@extends('layouts.admin')
@section('title', $voucher->code)
@section('content')
    <div class="voucher-edit-page">
        <a class="admin-back-link" href="{{ route('admin.vouchers.index') }}">← Danh sách voucher</a>
        <header class="voucher-editor-header">
            <div><span>CHỈNH SỬA VOUCHER</span>
                <h1>{{ $voucher->code }}</h1>
                <p>{{ $voucher->name }}</p>
            </div>
            <div class="voucher-editor-stats">
                <div><span>ĐÃ DÙNG</span><strong>{{ $voucher->used_count }}</strong></div>
                <div><span>GIỚI HẠN</span><strong>{{ $voucher->usage_limit ?? '∞' }}</strong></div>
            </div>
        </header>
        @if ($voucher->bills_count)
            <div class="alert alert-warning voucher-history-alert"><strong>Dữ liệu lịch sử đã được
                    khóa.</strong><span>{{ __('voucher.historical_locked') }}</span></div>
        @endif
        <form method="post" action="{{ route('admin.vouchers.update', $voucher) }}" class="voucher-editor-form">@csrf
            @method('put') @include('admin.vouchers._form')</form>
        <div class="voucher-danger-zone">
            <div><strong>Xóa voucher</strong><span>Voucher chỉ có thể xóa khi chưa phát sinh hóa đơn.</span></div>
            <form method="post" action="{{ route('admin.vouchers.destroy', $voucher) }}"
                onsubmit="return confirm('Bạn có chắc muốn xóa voucher này?');">@csrf @method('delete')<button
                    class="btn btn-outline-danger" @disabled($voucher->bills_count)>{{ __('app.delete') }}</button></form>
        </div>
    </div>
@endsection
