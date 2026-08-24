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
        @can('context.kitchen.access')
            <a class="me-3" href="{{ route('kitchen.home') }}">{{ __('app.contexts.kitchen') }}</a>
        @endcan
        @can('context.admin.access')
            <a href="{{ route('admin.home') }}">{{ __('app.contexts.admin') }}</a>
        @endcan
    </nav>
    <main class="container-fluid py-4">
        @yield('content')
    </main>
</body>
</html>
