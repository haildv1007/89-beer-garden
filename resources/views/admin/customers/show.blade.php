@extends('layouts.admin')
@section('title', $customer->name)
@section('content')
    <div class="customer-detail-page ops-detail-page">
        <header class="customer-profile-hero ops-detail-header">
            <a class="customer-profile-back" href="{{ route('admin.customers.index') }}">← Danh sách khách hàng</a>
            <div class="customer-profile-hero__main">
                @if ($customer->avatar_path)
                    <img class="customer-profile-avatar" src="{{ Storage::disk('public')->url($customer->avatar_path) }}"
                    alt="Ảnh đại diện của {{ $customer->name }}">@else<span
                        class="customer-profile-avatar">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                @endif
                <div class="customer-profile-hero__identity">
                    <h1>{{ $customer->name }}</h1>
                    <p>{{ $customer->phone ?: 'Chưa có số điện thoại' }}<span>•</span>{{ $customer->user ? 'Thành viên có tài khoản' : 'Khách quen / Walk-in' }}
                    </p>
                </div>
                @php
                    $isActiveMember = $customer->user?->status === 'active';
                @endphp
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge {{ $isActiveMember ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $isActiveMember ? 'Thành viên' : 'Khách quen' }}
                    </span>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.customers.edit', $customer) }}">
                        Chỉnh sửa
                    </a>
                    <form method="post" action="{{ route('admin.customers.destroy', $customer) }}"
                        onsubmit="return confirm('Lưu trữ hồ sơ này và vô hiệu hóa tài khoản đăng nhập? Lịch sử đơn hàng vẫn được giữ lại.')">
                        @csrf @method('delete')<button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                    </form>
                </div>
            </div>
        </header>

        @if ($duplicates->isNotEmpty())
            <section class="customer-duplicate-alert">
                <div><strong>Phát hiện {{ $duplicates->count() }} hồ sơ có cùng số điện thoại</strong>
                    <p>Kiểm tra tên và lịch sử trước khi gộp. Thao tác này không thể hoàn tác trực tiếp.</p>
                </div>
                <div class="customer-duplicate-list">
                    @foreach ($duplicates as $duplicate)
                        <article>
                            <div><strong>{{ $duplicate->name }}</strong><small>{{ $duplicate->user?->email ?? ($duplicate->email ?? 'Không có tài khoản') }}
                                    ·
                                    {{ $duplicate->reservations_count + $duplicate->dining_sessions_count + $duplicate->fulfillment_orders_count }}
                                    lượt</small></div>
                            @if ($customer->user_id && $duplicate->user_id && $customer->user_id !== $duplicate->user_id)
                                <span class="badge text-bg-warning">Cần kiểm tra thủ công</span>
                            @else
                                <form method="post" action="{{ route('admin.customers.merge', $customer) }}"
                                    onsubmit="return confirm('Gộp toàn bộ lịch sử của hồ sơ này vào hồ sơ hiện tại?')">
                                    @csrf<input type="hidden" name="source_customer_id"
                                        value="{{ $duplicate->id }}"><button class="btn btn-sm btn-outline-primary"
                                        type="submit">Gộp vào hồ sơ này</button></form>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="customer-profile-overview">
            <div class="customer-profile-facts">
                <div><span>Số điện thoại</span><strong>{{ $customer->phone ?: '—' }}</strong></div>
                <div><span>Email marketing</span><strong>{{ $customer->email ?: 'Chưa cung cấp' }}</strong></div>
                <div><span>Khách hàng từ</span><strong>{{ $customer->created_at->format('d/m/Y') }}</strong></div>
                <div><span>Lần gần
                        nhất</span><strong>{{ $metrics['last_activity_at']?->format('d/m/Y H:i') ?? '—' }}</strong></div>
            </div>
            <div class="customer-profile-stats">
                <div><span>Tổng đơn</span><strong>{{ $metrics['total_orders'] }}</strong></div>
                <div><span>Đơn hoàn thành</span><strong>{{ $metrics['completed_orders'] }}</strong></div>
                <div><span>Lượt đặt bàn</span><strong>{{ $customer->reservations_count }}</strong><small>Yêu cầu giữ chỗ,
                        không phải đơn hàng</small></div>
                <div><span>Tổng chi tiêu ghi nhận</span><strong>{{ number_format($metrics['spending']) }} ₫</strong></div>
            </div>
        </section>

        <section class="customer-activity-section">
            <div class="customer-section-heading">
                <div>
                    <h2>Lịch sử hoạt động</h2>
                    <p>Đặt bàn và các lần phục vụ gần đây của khách.</p>
                </div>
            </div>
            <form class="customer-activity-filter" method="get">
                <select class="form-select" name="type">
                    <option value="">Tất cả hoạt động</option>
                    <option value="reservation" @selected(($filters['type'] ?? '') === 'reservation')>Đặt bàn</option>
                    <option value="dine_in" @selected(($filters['type'] ?? '') === 'dine_in')>Tại quán</option>
                    <option value="pickup" @selected(($filters['type'] ?? '') === 'pickup')>Đến lấy</option>
                    <option value="delivery" @selected(($filters['type'] ?? '') === 'delivery')>Giao hàng</option>
                </select>
                <input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                    aria-label="Từ ngày"><input class="form-control" type="date" name="to"
                    value="{{ $filters['to'] ?? '' }}" aria-label="Đến ngày"><button class="btn btn-primary">Lọc</button>
                @if (array_filter($filters))
                    <a class="btn btn-outline-secondary" href="{{ route('admin.customers.show', $customer) }}">Xóa lọc</a>
                @endif
            </form>
            <div class="customer-timeline">
                @forelse($activities as $activity)
                    @php
                        $statusLabels = [
                            'pending' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'checked-in' => 'Đã check-in',
                            'completed' => 'Hoàn tất',
                            'rejected' => 'Từ chối',
                            'cancelled' => 'Đã hủy',
                            'no-show' => 'Không đến',
                            'active' => 'Đang phục vụ',
                        ];
                        $isMutedStatus = in_array($activity['status'], ['rejected', 'cancelled', 'no-show'], true);
                        $statusClass = match (true) {
                            $activity['status'] === 'pending' => 'text-bg-warning',
                            $isMutedStatus => 'text-bg-secondary',
                            default => 'text-bg-success',
                        };
                    @endphp
                    <article class="customer-timeline-item customer-timeline-item--{{ $activity['type'] }}">
                        <span class="customer-timeline-dot"></span>
                        <div class="customer-timeline-date">
                            <strong>{{ $activity['occurred_at']?->format('d/m/Y') }}</strong><small>{{ $activity['occurred_at']?->format('H:i') }}</small>
                        </div>
                        <div class="customer-timeline-content">
                            <div><span
                                    class="customer-activity-type">{{ $activity['label'] }}</span><strong>{{ $activity['description'] }}</strong><small>{{ $activity['code'] }}
                                    @if ($activity['scheduled_at'])
                                        · Hẹn {{ $activity['scheduled_at']->format('d/m/Y H:i') }}
                                    @endif
                                </small>
                            </div>
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusLabels[$activity['status']] ?? $activity['status'] }}
                            </span>
                        </div>
                        <div class="customer-timeline-amount">
                            {{ $activity['amount'] !== null ? number_format($activity['amount']) . ' ₫' : '—' }}</div>
                    </article>
                @empty<div class="customer-activity-empty"><strong>Chưa có hoạt động phù hợp</strong>
                        <p>Lịch sử của khách sẽ xuất hiện tại đây khi có đặt bàn hoặc đơn hàng.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-3">{{ $activities->links() }}</div>
        </section>
    </div>
@endsection
