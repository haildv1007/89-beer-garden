<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('customer.home') }}">{{ __('app.name') }}</a>
            <div class="navbar-nav flex-row gap-3 ms-auto">
                @foreach(config('localization.supported_locales') as $locale)<a class="nav-link" href="{{ route('locale.switch',$locale) }}">{{ strtoupper($locale) }}</a>@endforeach
                <a class="nav-link" href="{{ route('customer.menu.index') }}">{{ __('app.menu.title') }}</a>
                <a class="nav-link" href="{{ route('customer.reservations.create') }}">{{ __('reservation.customer.make') }}</a>
                @if (session()->has(\App\Services\CustomerOrder\CustomerDiningContextService::SESSION_KEY))
                    <a class="nav-link" href="{{ route('customer.cart.index') }}">{{ __('customer_order.cart') }} ({{ collect(session(\App\Services\CustomerOrder\CustomerCartService::SESSION_KEY, []))->sum('quantity') }})</a>
                    <a class="nav-link" href="{{ route('customer.orders.current') }}">{{ __('customer_order.current_status') }}</a>
                @endif
                @guest
                    <a class="nav-link" href="{{ route('customer.registration.create') }}">{{ __('customer.registration.action') }}</a>
                    <a class="nav-link" href="{{ route('login') }}">{{ __('app.login') }}</a>
                @else
                    @can('customer.profile.manage-own')
                        @if (auth()->user()->customer)<a class="nav-link" href="{{ route('customer.profile.show', auth()->user()->customer) }}">{{ __('customer.profile.title') }}</a>@endif
                    @endcan
                    @can('customer.reservation.view-own')<a class="nav-link" href="{{ route('customer.reservations.index') }}">{{ __('reservation.customer.mine') }}</a>@endcan
                    @can('customer.order.view-own')<a class="nav-link" href="{{ route('customer.orders.history') }}">{{ __('order_history.title') }}</a>@endcan
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-link nav-link p-0">{{ __('customer.logout') }}</button></form>
                @endguest
            </div>
        </div>
    </nav>
    @auth
        <nav class="container pt-3" aria-label="{{ __('app.context_navigation') }}">
            @can('context.pos.access')
                <a class="me-3" href="{{ route('pos.home') }}">{{ __('app.contexts.pos') }}</a>
            @endcan
            @can('context.kitchen.access')
                <a class="me-3" href="{{ route('kitchen.home') }}">{{ __('app.contexts.kitchen') }}</a>
            @endcan
            @can('context.admin.access')
                <a href="{{ route('admin.home') }}">{{ __('app.contexts.admin') }}</a>
            @endcan
        </nav>
    @endauth
    @if (session('success'))<div class="container mt-3"><div class="alert alert-success">{{ session('success') }}</div></div>@endif
    <main class="container py-5">
        @yield('content')
    </main>
</body>
</html>
