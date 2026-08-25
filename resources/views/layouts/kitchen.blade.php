<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="container-fluid pt-3" aria-label="{{ __('app.context_navigation') }}">
        @can('kitchen.queue.view')
            <a class="me-3" href="{{ route('kitchen.home') }}">{{ __('kitchen.title') }}</a>
        @endcan
        @can('context.pos.access')
            <a class="me-3" href="{{ route('pos.home') }}">{{ __('app.contexts.pos') }}</a>
        @endcan
        @can('context.admin.access')
            <a href="{{ route('admin.home') }}">{{ __('app.contexts.admin') }}</a>
        @endcan
    </nav>
    @if (session('success'))
        <div class="container-fluid mt-3"><div class="alert alert-success">{{ session('success') }}</div></div>
    @endif
    <main class="container-fluid py-4">
        @yield('content')
    </main>
</body>
</html>
