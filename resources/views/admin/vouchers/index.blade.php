@extends('layouts.admin')
@section('title', __('voucher.title'))
@section('content')
    <div class="voucher-management-page">
        <div class="admin-section-heading voucher-page-heading">
            <div>
                <h1>{{ __('voucher.title') }}</h1>
                <p>Tạo và theo dõi chương trình ưu đãi, điều kiện áp dụng, thời hạn và số lượt sử dụng.</p>
            </div><a class="btn btn-primary" href="{{ route('admin.vouchers.create') }}">+ {{ __('voucher.create') }}</a>
        </div>
        <form class="admin-list-filter voucher-list-filter"><input class="form-control" name="q"
                value="{{ $search }}" placeholder="{{ __('voucher.search') }}"><select class="form-select"
                name="status">
                <option value="">{{ __('voucher.all_statuses') }}</option>
                <option value="active" @selected($status === 'active')>{{ __('voucher.statuses.active') }}</option>
                <option value="inactive" @selected($status === 'inactive')>{{ __('voucher.statuses.inactive') }}</option>
            </select><button class="btn btn-primary">{{ __('app.search') }}</button></form>
        @if ($vouchers->isEmpty())
            <div class="admin-empty-state"><strong>{{ __('voucher.empty') }}</strong><span>Thử thay đổi từ khóa hoặc bộ lọc
                    để tìm voucher.</span></div>
        @else
            <div class="admin-data-shell voucher-list-shell">
                <table class="table table-hover align-middle admin-data-table voucher-list-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Voucher</th>
                            <th>Ưu đãi</th>
                            <th>Điều kiện áp dụng</th>
                            <th>Thời hạn</th>
                            <th>{{ __('voucher.fields.used') }}</th>
                            <th>{{ __('voucher.fields.status') }}</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vouchers as $voucher)
                            @php
                                $isExpired = $voucher->end_at->isPast();
                            @endphp
                            @php
                                $discountValue =
                                    $voucher->discount_type === 'percentage'
                                        ? number_format($voucher->discount_value) . '%'
                                        : number_format($voucher->discount_value, 0, ',', '.') . ' ₫';
                                $maximumDiscount = $voucher->max_discount_amount
                                    ? 'Tối đa ' . number_format($voucher->max_discount_amount, 0, ',', '.') . ' ₫'
                                    : 'Không giới hạn mức giảm';
                                $remainingUses = $voucher->usage_limit
                                    ? max(0, $voucher->usage_limit - $voucher->used_count) . ' lượt còn lại'
                                    : 'Không giới hạn lượt';
                            @endphp
                            @php
                                $isUpcoming = $voucher->start_at->isFuture();
                            @endphp
                            <tr>
                                <td class="voucher-row-number">{{ $vouchers->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="voucher-identity">
                                        <strong>{{ $voucher->code }}</strong><span>{{ $voucher->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <strong class="voucher-value">{{ $discountValue }}</strong>
                                    <small>{{ __('voucher.types.' . $voucher->discount_type) }}</small>
                                </td>
                                <td><strong>Từ {{ number_format($voucher->min_order_amount, 0, ',', '.') }}
                                        ₫</strong><small>{{ $maximumDiscount }}</small>
                                </td>
                                <td><strong>{{ $voucher->start_at->format('d/m/Y H:i') }}</strong><small>đến
                                        {{ $voucher->end_at->format('d/m/Y H:i') }}</small></td>
                                <td><strong>{{ $voucher->used_count }} /
                                        {{ $voucher->usage_limit ?? '∞' }}</strong><small>{{ $remainingUses }}</small>
                                </td>
                                <td>
                                    @if ($voucher->status === 'inactive')
                                        <span class="admin-status-badge">Không hoạt động</span>
                                    @elseif($isExpired)
                                        <span class="admin-status-badge is-danger">Đã hết hạn</span>
                                    @elseif($isUpcoming)
                                    <span class="admin-status-badge is-warning">Sắp diễn ra</span>@else<span
                                            class="admin-status-badge is-success">Đang áp dụng</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="voucher-row-actions"><a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('admin.vouchers.edit', $voucher) }}">Chỉnh sửa</a>
                                        <form method="post" action="{{ route('admin.vouchers.destroy', $voucher) }}"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa voucher này?');">@csrf
                                            @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit"
                                                @disabled($voucher->bills_count)
                                                title="{{ $voucher->bills_count ? 'Voucher đã phát sinh hóa đơn nên không thể xóa' : 'Xóa voucher' }}">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-pagination">{{ $vouchers->links() }}</div>
        @endif
    </div>
@endsection
