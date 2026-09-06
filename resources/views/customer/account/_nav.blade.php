@php
    $accountCustomer = $customer ?? auth()->user()?->customer;
@endphp
<nav class="account-nav" aria-label="{{ __('customer.navigation.label') }}">
    @if ($accountCustomer)
        <a class="{{ request()->routeIs('customer.profile.*') ? 'is-active' : '' }}"
            href="{{ route('customer.profile.show') }}">{{ __('customer.navigation.profile') }}</a>
    @endif
    <a class="{{ request()->routeIs('customer.reservations.*') ? 'is-active' : '' }}"
        href="{{ route('customer.reservations.index') }}">{{ __('customer.navigation.reservations') }}</a>
    <a class="{{ request()->routeIs('customer.orders.history*') ? 'is-active' : '' }}"
        href="{{ route('customer.orders.history') }}">{{ __('customer.navigation.orders') }}</a>
</nav>
