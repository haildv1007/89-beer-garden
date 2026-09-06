<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/css/pos.css', 'resources/js/app.js'])
</head>

<body class="operation-shell">
    <a class="visually-hidden-focusable btn btn-warning position-absolute m-2"
        href="#main-content">{{ __('app.skip_to_content') }}</a>
    <header class="operation-header" data-operation-header>
        <div class="operation-header__bar">
            <a class="operation-brand" href="{{ route('pos.home') }}">
                <img class="operation-brand__mark" src="{{ asset('images/brand/quan-89-logo.png') }}" alt=""
                    aria-hidden="true">
                <span><strong>{{ __('app.name') }}</strong><small>{{ __('app.contexts.pos') }}</small></span>
            </a>
            <button class="operation-menu-toggle btn" type="button" aria-label="{{ __('app.open_navigation') }}"
                aria-expanded="false" data-operation-menu>☰</button>
            <nav class="operation-nav" aria-label="{{ __('app.context_navigation') }}">
                @can('table.view')
                    <a class="{{ request()->routeIs('pos.tables.*') ? 'is-active' : '' }}"
                        href="{{ route('pos.tables.index') }}">{{ __('table.pos.map') }}</a>
                @endcan
                @can('reservation.manage')
                    <a class="{{ request()->routeIs('pos.reservations.*') ? 'is-active' : '' }}"
                        href="{{ route('pos.reservations.index') }}">{{ __('reservation.internal.title') }}</a>
                @endcan
                @can('order.create')
                    <a class="{{ request()->routeIs('pos.fulfillment-orders.*') ? 'is-active' : '' }}"
                        href="{{ route('pos.fulfillment-orders.index') }}">{{ __('fulfillment_order.title') }}</a>
                @endcan
                @can('dining-session.view')
                    <a class="{{ request()->routeIs('pos.dining-sessions.*') || request()->routeIs('pos.orders.*') || request()->routeIs('pos.bills.*') ? 'is-active' : '' }}"
                        href="{{ route('pos.dining-sessions.index') }}">{{ __('dining_session.title') }}</a>
                @endcan
                @can('context.kitchen.access')
                    <a href="{{ route('kitchen.home') }}">{{ __('app.contexts.kitchen') }}</a>
                @endcan
                @can('context.admin.access')
                    <a href="{{ route('admin.tables.index') }}">{{ __('app.contexts.admin') }}</a>
                @endcan
            </nav>
            <div class="operation-actor">
                <div class="operation-actor__name">
                    <strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role?->name ?? __('app.contexts.pos') }}</small>
                </div>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light"
                        type="submit">{{ __('app.logout') }}</button></form>
            </div>
        </div>
    </header>
    @if (session('success') || $errors->any())
        <div class="operation-flash" aria-live="polite">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
        </div>
    @endif
    <main class="operation-main" id="main-content">@yield('content')</main>
    <script>
        document.querySelector('[data-operation-menu]')?.addEventListener('click', function() {
            const header = document.querySelector('[data-operation-header]');
            const open = header.classList.toggle('is-open');
            this.setAttribute('aria-expanded', open ? 'true' : 'false')
        });
    </script>
    @stack('scripts')
</body>

</html>
