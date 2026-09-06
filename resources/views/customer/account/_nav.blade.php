@php
    $accountCustomer = $customer ?? auth()->user()?->customer;
@endphp
<nav class="account-nav" aria-label="Khu vực tài khoản">
    @if ($accountCustomer)
        <a class="{{ request()->routeIs('customer.profile.*') ? 'is-active' : '' }}"
            href="{{ route('customer.profile.show') }}">Hồ sơ</a>
    @endif
    <a class="{{ request()->routeIs('customer.reservations.*') ? 'is-active' : '' }}"
        href="{{ route('customer.reservations.index') }}">Đặt bàn của tôi</a>
    <a class="{{ request()->routeIs('customer.orders.history*') ? 'is-active' : '' }}"
        href="{{ route('customer.orders.history') }}">Lịch sử gọi món</a>
</nav>
