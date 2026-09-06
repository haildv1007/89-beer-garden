@extends('layouts.admin')
@section('title', __('customer.admin.title'))
@section('content')
    <div class="customer-directory-page ops-page">
        <header class="ops-page-heading">
            <div>
                <h1>{{ __('customer.admin.title') }}</h1>
                <p>Tra cứu hồ sơ, lịch sử giao dịch và mức độ hoạt động của khách hàng.</p>
            </div>
        </header>
        <section class="customer-directory-summary ops-summary">
            <div><span>Tổng khách hàng</span><strong>{{ $summary['customers'] }}</strong><small>Số điện thoại duy
                    nhất</small></div>
            <div><span>Thành viên</span><strong>{{ $summary['members'] }}</strong><small>Có tài khoản hoạt động</small>
            </div>
            <div><span>Khách thường</span><strong>{{ $summary['guests'] }}</strong><small>Chưa có tài khoản</small></div>
        </section>
        <form class="customer-search customer-search--directory ops-filter" method="get"><select class="form-select"
                name="account" aria-label="Loại khách hàng">
                <option value="">Tất cả khách</option>
                <option value="member" @selected($account === 'member')>Thành viên</option>
                <option value="guest" @selected($account === 'guest')>Khách thường</option>
            </select><input class="form-control" name="q" value="{{ $search }}"
                placeholder="{{ __('customer.admin.search_placeholder') }}"><button
                class="btn btn-primary">{{ __('app.search') }}</button></form>
        @if ($customers->isEmpty())
            <div class="admin-empty-state"><span>♙</span>
                <p>{{ __('customer.admin.empty') }}</p>
            </div>
        @else
            @php
                $sortParams = request()->except('page', 'sort', 'direction');
                $sortLink = function (string $column) use ($sort, $direction, $sortParams) {
                    if ($sort !== $column) {
                        return route(
                            'admin.customers.index',
                            array_merge($sortParams, ['sort' => $column, 'direction' => 'desc']),
                        );
                    }
                    if ($direction === 'desc') {
                        return route(
                            'admin.customers.index',
                            array_merge($sortParams, ['sort' => $column, 'direction' => 'asc']),
                        );
                    }
                    return route('admin.customers.index', $sortParams);
                };
            @endphp
            <div class="customer-list-shell ops-data-shell">
                <table class="table align-middle customer-list customer-list--simple ops-data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Loại khách</th>
                            <th><a @class([
                                'customer-sort-link',
                                'active' => $sort === 'total_orders',
                                'is-desc' => $sort === 'total_orders' && $direction === 'desc',
                                'is-asc' => $sort === 'total_orders' && $direction === 'asc',
                            ]) href="{{ $sortLink('total_orders') }}">Tổng đơn <span
                                        class="customer-sort-mark" aria-hidden="true"></span></a></th>
                            <th><a @class([
                                'customer-sort-link',
                                'active' => $sort === 'completed_orders',
                                'is-desc' => $sort === 'completed_orders' && $direction === 'desc',
                                'is-asc' => $sort === 'completed_orders' && $direction === 'asc',
                            ]) href="{{ $sortLink('completed_orders') }}">Đơn hoàn thành
                                    <span class="customer-sort-mark" aria-hidden="true"></span></a></th>
                            <th>Chi tiêu</th>
                            <th>Hoạt động gần nhất</th>
                            <th class="customer-action-column">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            @php
                                $totalOrders =
                                    $customer->dining_order_sessions_count + $customer->takeaway_orders_count;
                                $completedOrders =
                                    $customer->completed_dining_sessions_count +
                                    $customer->completed_fulfillment_orders_count;
                                $spending = (int) $customer->dining_spending + (int) $customer->takeaway_spending;
                            @endphp
                            @php
                                $lastActivity = collect([
                                    $customer->last_reservation_at,
                                    $customer->last_dining_at,
                                    $customer->last_fulfillment_at,
                                ])
                                    ->filter()
                                    ->map(fn($date) => \Carbon\CarbonImmutable::parse($date))
                                    ->sortDesc()
                                    ->first();
                                $isActiveMember = $customer->user?->status === 'active';
                            @endphp
                            <tr>
                                <td><strong>{{ ($customers->firstItem() ?? 1) + $loop->index }}</strong></td>
                                <td><a class="customer-identity" href="{{ route('admin.customers.show', $customer) }}">
                                        @if ($customer->avatar_path)
                                            <img src="{{ Storage::disk('public')->url($customer->avatar_path) }}"
                                            alt="">@else<span>{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                                        @endif
                                        <div><strong>{{ $customer->name }}</strong></div>
                                    </a></td>
                                <td><strong>{{ $customer->phone ?: '—' }}</strong><small>{{ $customer->user?->email ?? ($customer->email ?? 'Chưa có email') }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $isActiveMember ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $isActiveMember ? 'Thành viên' : 'Khách thường' }}
                                    </span>
                                </td>
                                <td><strong>{{ $totalOrders }}</strong></td>
                                <td><strong>{{ $completedOrders }}</strong></td>
                                <td><strong>{{ number_format($spending) }} ₫</strong></td>
                                <td><strong>{{ $lastActivity?->format('d/m/Y') ?? '—' }}</strong><small>{{ $lastActivity?->format('H:i') }}</small>
                                </td>
                                <td class="text-end customer-action-column"><a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('admin.customers.show', $customer) }}">Mở hồ sơ</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
