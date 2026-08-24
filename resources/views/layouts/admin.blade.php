<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-dark bg-dark px-3" aria-label="{{ __('app.context_navigation') }}">
        <a class="navbar-brand" href="{{ route('admin.home') }}">{{ __('app.contexts.admin') }}</a>
        <div class="navbar-nav flex-row gap-3">
        @can('category.manage')<a class="nav-link" href="{{ route('admin.categories.index') }}">{{ __('app.categories.title') }}</a>@endcan
        @can('product.manage')<a class="nav-link" href="{{ route('admin.products.index') }}">{{ __('app.products.title') }}</a>@endcan
        @can('context.pos.access')
            <a class="nav-link" href="{{ route('pos.home') }}">{{ __('app.contexts.pos') }}</a>
        @endcan
        @can('context.kitchen.access')
            <a class="nav-link" href="{{ route('kitchen.home') }}">{{ __('app.contexts.kitchen') }}</a>
        @endcan
        </div>
    </nav>
    @if (session('success'))<div class="container-fluid mt-3"><div class="alert alert-success">{{ session('success') }}</div></div>@endif
    <main class="container-fluid py-4">
        @yield('content')
    </main>
</body>
</html>
