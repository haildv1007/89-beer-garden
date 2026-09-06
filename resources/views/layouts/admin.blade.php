<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>

<body class="admin-context">
    <div class="admin-shell" data-admin-shell>
        <aside class="admin-sidebar" id="admin-sidebar" aria-label="{{ __('app.context_navigation') }}">
            <div class="admin-brand">
                <a href="{{ route('admin.tables.index') }}"><img class="admin-brand-mark"
                        src="{{ asset('images/brand/quan-89-logo.png') }}" alt="" aria-hidden="true"></a>
                <span><strong>Beer Garden</strong><small>{{ __('app.contexts.admin') }}</small></span>
                <button class="admin-sidebar-close" type="button" data-sidebar-toggle
                    aria-label="Close navigation">×</button>
            </div>
            <nav class="admin-nav">
                <div class="admin-nav-group"><span class="admin-nav-label">Operations</span>
                    @can('restaurant-table.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs(
                                'admin.home',
                                'admin.tables.*',
                                'admin.restaurant-tables.*'),
                        ]) href="{{ route('admin.tables.index') }}"><span
                                aria-hidden="true">▦</span>{{ __('table.title') }}</a>
                    @endcan
                    @can('reservation.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.reservations.*'),
                        ]) href="{{ route('admin.reservations.index') }}"><span
                                aria-hidden="true">◷</span>{{ __('reservation.internal.title') }}</a>
                    @endcan
                    @can('order.create')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.fulfillment-orders.*'),
                        ]) href="{{ route('admin.fulfillment-orders.index') }}"><span
                                aria-hidden="true">▤</span>Đơn ngoài quán</a>
                    @endcan
                    @can('dining-session.view')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.dining-sessions.*'),
                        ]) href="{{ route('admin.dining-sessions.index') }}"><span
                                aria-hidden="true">◉</span>Phiên phục vụ</a>
                    @endcan
                    @can('customer.view')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.customers.*'),
                        ]) href="{{ route('admin.customers.index') }}"><span
                                aria-hidden="true">♙</span>{{ __('customer.admin.title') }}</a>
                    @endcan
                </div>
                <div class="admin-nav-group"><span class="admin-nav-label">Menu</span>
                    @can('category.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.categories.*'),
                        ]) href="{{ route('admin.categories.index') }}"><span
                                aria-hidden="true">≡</span>{{ __('app.categories.title') }}</a>
                    @endcan
                    @can('product.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.products.*'),
                        ]) href="{{ route('admin.products.index') }}"><span
                                aria-hidden="true">◇</span>{{ __('app.products.title') }}</a>
                    @endcan
                    @can('post.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.posts.*'),
                        ]) href="{{ route('admin.posts.index') }}"><span
                                aria-hidden="true"><svg viewBox="0 0 24 24">
                                    <path d="M4 5h12v14H4zM16 8h4v9a2 2 0 0 1-2 2h-2M7 8h6M7 11h6M7 14h3M12 14h1" />
                                </svg></span>Tin tức</a>
                    @endcan
                </div>
                <div class="admin-nav-group"><span class="admin-nav-label">Business</span>
                    @can('voucher.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.vouchers.*'),
                        ]) href="{{ route('admin.vouchers.index') }}"><span
                                aria-hidden="true">%</span>{{ __('voucher.title') }}</a>
                    @endcan
                    @if (config('features.inventory'))
                        @can('inventory.view')
                            <a @class([
                                'admin-nav-link',
                                'active' => request()->routeIs('admin.inventory-items.*'),
                            ]) href="{{ route('admin.inventory-items.index') }}"><span
                                    aria-hidden="true">▤</span>{{ __('inventory.title') }}</a>
                        @endcan
                    @endif
                    @can('report.view')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.reports.*'),
                        ]) href="{{ route('admin.reports.index') }}"><span
                                aria-hidden="true">↗</span>{{ __('report.title') }}</a>
                    @endcan
                </div>
                <div class="admin-nav-group"><span class="admin-nav-label">System</span>
                    @can('employee.manage')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.employees.*'),
                        ]) href="{{ route('admin.employees.index') }}"><span
                                aria-hidden="true">♙</span>{{ __('employee.employees.title') }}</a>
                    @endcan
                    @can('permission.assign')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.roles.*'),
                        ]) href="{{ route('admin.roles.index') }}"><span
                                aria-hidden="true">⌘</span>{{ __('employee.roles.title') }}</a>
                    @endcan
                    @can('settings.update')
                        <a @class([
                            'admin-nav-link',
                            'active' => request()->routeIs('admin.settings.*'),
                        ]) href="{{ route('admin.settings.index') }}"><span
                                aria-hidden="true">⚙</span>{{ __('setting.title') }}</a>
                    @endcan
                </div>
            </nav>
            <div class="admin-context-links">
                @can('context.pos.access')
                    <a href="{{ route('pos.home') }}">{{ __('app.contexts.pos') }} ↗</a>
                    @endcan @can('context.kitchen.access')
                    <a href="{{ route('kitchen.home') }}">{{ __('app.contexts.kitchen') }} ↗</a>
                @endcan
            </div>
        </aside>
        <div class="admin-overlay" data-sidebar-toggle></div>
        <div class="admin-workspace">
            <header class="admin-topbar">
                <button class="admin-menu-toggle" type="button" data-sidebar-toggle aria-controls="admin-sidebar"
                    aria-expanded="false"><span></span><span></span><span></span><span
                        class="visually-hidden">Menu</span></button>
                <div class="admin-topbar-title">
                    <span>{{ __('app.contexts.admin') }}</span><strong>@yield('title', __('app.name'))</strong>
                </div>
                <div class="admin-topbar-actions">
                    <div class="admin-actor">
                        <span>{{ auth()->user()->employee?->name ?? auth()->user()->email }}</span><small>{{ auth()->user()->role?->name }}</small>
                    </div>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="admin-logout"
                            type="submit">{{ __('customer.logout') }}</button></form>
                </div>
            </header>
            <div class="admin-flashes" aria-live="polite">
                @if (session('success'))
                    <div class="alert alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                @endif
            </div>
            <main class="admin-main">
                @yield('content')
            </main>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => button.addEventListener('click', () => {
            const shell = document.querySelector('[data-admin-shell]');
            const isOpen = shell.classList.toggle('sidebar-open');
            document.querySelector('.admin-menu-toggle')?.setAttribute('aria-expanded', String(isOpen));
        }));
    </script>
</body>

</html>
