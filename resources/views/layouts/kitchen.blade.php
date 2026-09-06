<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/css/kitchen.css', 'resources/js/app.js'])
</head>

<body class="kds-shell">
    <header class="kds-header">
        <a class="kds-brand" href="{{ route('kitchen.home') }}"><img class="kds-brand__mark"
                src="{{ asset('images/brand/quan-89-logo.png') }}" alt="" aria-hidden="true"><span><strong>Phiếu
                    bếp</strong><small>{{ __('app.name') }}</small></span></a>
        <div class="kds-header__actions">
            <span class="kds-header__actor">{{ auth()->user()->name }}</span>
            @can('context.pos.access')
                <a class="btn btn-sm btn-outline-light" href="{{ route('pos.home') }}">{{ __('app.contexts.pos') }}</a>
            @endcan
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light"
                    type="submit">{{ __('app.logout') }}</button></form>
        </div>
    </header>
    <main class="kds-main">
        @if (session('success') || $errors->any())
            <div class="kds-flash" aria-live="polite">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                    @endif @if ($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif
            </div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>

</html>
