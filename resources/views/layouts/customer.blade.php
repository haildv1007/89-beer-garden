<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@php
    $publicSettings = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->publicSiteSettings();
    $siteName = $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_NAME] ?? 'Beer Garden';
    $siteAuthor = $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_AUTHOR] ?? null;
    $declaredTitle = trim($__env->yieldContent('title'));
    $documentTitle = request()->routeIs('customer.home')
        ? ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SEO_TITLE] ?? $declaredTitle)
        : ($declaredTitle ?: ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SEO_TITLE] ?? $siteName));
    $metaDescription = trim($__env->yieldContent('meta_description')) ?:
        ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SEO_DESCRIPTION] ?? null);
    $storedAssetUrl = static fn (string $key): ?string => isset($publicSettings[$key])
        ? Storage::disk('public')->url($publicSettings[$key])
        : null;
    $siteLogoUrl = $storedAssetUrl(\App\Services\SystemSetting\SystemSettingCatalog::SITE_LOGO)
        ?? asset('images/brand/quan-89-logo.png');
    $siteBannerUrl = $storedAssetUrl(\App\Services\SystemSetting\SystemSettingCatalog::SITE_BANNER);
    $faviconUrl = $storedAssetUrl(\App\Services\SystemSetting\SystemSettingCatalog::SITE_FAVICON);
    $ogImageUrl = $storedAssetUrl(\App\Services\SystemSetting\SystemSettingCatalog::OG_IMAGE) ?? $siteBannerUrl;
    $twitterImageUrl = $storedAssetUrl(\App\Services\SystemSetting\SystemSettingCatalog::TWITTER_IMAGE) ?? $ogImageUrl;
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b3525">
    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if ($siteAuthor)
        <meta name="author" content="{{ $siteAuthor }}">
    @endif
    @if ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SEO_KEYWORDS] ?? null)
        <meta name="keywords" content="{{ $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::SEO_KEYWORDS] }}">
    @endif
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <title>{{ $documentTitle }}</title>
    @unless (request()->routeIs('customer.posts.show'))
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::OG_TITLE] ?? $documentTitle }}">
        @if ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::OG_DESCRIPTION] ?? $metaDescription)
            <meta property="og:description" content="{{ $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::OG_DESCRIPTION] ?? $metaDescription }}">
        @endif
        <meta property="og:url" content="{{ url()->current() }}">
        @if ($ogImageUrl)
            <meta property="og:image" content="{{ url($ogImageUrl) }}">
        @endif
        <meta name="twitter:card" content="{{ $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::TWITTER_CARD] ?? 'summary_large_image' }}">
        <meta name="twitter:title" content="{{ $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::TWITTER_TITLE] ?? ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::OG_TITLE] ?? $documentTitle) }}">
        @if ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::TWITTER_DESCRIPTION] ?? ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::OG_DESCRIPTION] ?? $metaDescription))
            <meta name="twitter:description" content="{{ $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::TWITTER_DESCRIPTION] ?? ($publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::OG_DESCRIPTION] ?? $metaDescription) }}">
        @endif
        @if ($twitterImageUrl)
            <meta name="twitter:image" content="{{ url($twitterImageUrl) }}">
        @endif
    @endunless
    @stack('seo')
    {!! $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::CUSTOM_SCRIPT_HEAD] ?? '' !!}
    @vite(['resources/css/app.css', 'resources/css/customer.css', 'resources/js/app.js'])
    @vite('resources/css/mobile-navigation.css')
    @if (request()->routeIs('customer.home'))
        @vite('resources/css/home-typography.css')
    @endif
</head>

<body>
    {!! $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::CUSTOM_SCRIPT_BODY] ?? '' !!}
    <a class="visually-hidden-focusable position-fixed top-0 start-0 z-3 m-2 btn btn-light"
        href="#main-content">{{ __('customer_ui.skip') }}</a>
    <header @class([
        'site-header',
        'site-header--home' => request()->routeIs('customer.home'),
    ])>
        <nav class="navbar navbar-expand-lg navbar-light site-navbar" aria-label="{{ __('customer_ui.navigation') }}">
            <div class="container">
                @php
                    $localeOptions = [
                        'vi' => ['flag' => 'vi', 'label' => 'Tiếng Việt'],
                        'en' => ['flag' => 'en', 'label' => 'English'],
                        'zh' => ['flag' => 'zh', 'label' => '中文'],
                    ];
                    $currentLocale = $localeOptions[app()->getLocale()] ?? $localeOptions['vi'];
                @endphp
                <a class="brand-lockup" href="{{ route('customer.home') }}" aria-label="{{ $siteName }}">
                    <img class="brand-mark" src="{{ $siteLogoUrl }}" alt=""
                        aria-hidden="true">
                    <span class="brand-wordmark">{{ $siteName }}<small>{{ __('customer_ui.brand_tagline') }}</small></span>
                </a>
                <div class="mobile-header-actions d-lg-none">
                    <div class="dropdown mobile-locale-switcher">
                        <button class="mobile-locale-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="{{ __('translation.locale') }}: {{ $currentLocale['label'] }}">
                            <img class="locale-flag"
                                src="{{ asset('images/flags/' . $currentLocale['flag'] . '.svg') }}"
                                alt="{{ $currentLocale['label'] }}">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end locale-menu mobile-locale-menu">
                            @foreach (config('localization.supported_locales') as $locale)
                                @php
                                    $option = $localeOptions[$locale];
                                @endphp
                                <li><a @class(['dropdown-item', 'active' => app()->getLocale() === $locale])
                                        @if (app()->getLocale() === $locale) aria-current="true" @endif
                                        href="{{ route('locale.switch', $locale) }}"><img class="locale-flag"
                                            src="{{ asset('images/flags/' . $option['flag'] . '.svg') }}"
                                            alt=""><span>{{ $option['label'] }}</span></a></li>
                            @endforeach
                        </ul>
                    </div>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#customerNavigation" aria-controls="customerNavigation" aria-expanded="false"
                        aria-label="{{ __('customer_ui.navigation') }}"><span class="navbar-toggler-icon"></span></button>
                </div>
                <div class="collapse navbar-collapse" id="customerNavigation">
                    <ul class="navbar-nav mx-lg-auto gap-lg-2">
                        <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('customer.home')])
                                @if (request()->routeIs('customer.home')) aria-current="page" @endif
                                href="{{ route('customer.home') }}">{{ __('customer_ui.home_navigation') }}</a></li>
                        <li class="nav-item"><a @class([
                            'nav-link',
                            'active' => request()->routeIs('customer.menu.*', 'customer.products.*'),
                        ])
                                @if (request()->routeIs('customer.menu.*', 'customer.products.*')) aria-current="page" @endif
                                href="{{ route('customer.menu.index') }}">{{ __('app.menu.title') }}</a></li>
                        <li class="nav-item"><a @class([
                            'nav-link',
                            'active' => request()->routeIs('customer.posts.*'),
                        ])
                                @if (request()->routeIs('customer.posts.*')) aria-current="page" @endif
                                href="{{ route('customer.posts.index') }}">{{ __('customer_ui.news_navigation') }}</a></li>
                        <li class="nav-item">
                            <a @class(['nav-link', 'nav-reservation', 'active' => request()->routeIs('customer.reservations.*')])
                                @if (request()->routeIs('customer.reservations.*')) aria-current="page" @endif
                                href="{{ route('customer.reservations.create') }}">{{ __('reservation.customer.make') }}</a>
                        </li>
                    </ul>
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3">
                        <div class="dropdown locale-switcher desktop-locale-switcher">
                            <button class="locale-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false" aria-label="{{ __('translation.locale') }}">
                                <img class="locale-flag"
                                    src="{{ asset('images/flags/' . $currentLocale['flag'] . '.svg') }}"
                                    alt="">
                                <span class="locale-current-label">{{ $currentLocale['label'] }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end locale-menu">
                                @foreach (config('localization.supported_locales') as $locale)
                                    @php
                                        $option = $localeOptions[$locale];
                                    @endphp
                                    <li><a @class(['dropdown-item', 'active' => app()->getLocale() === $locale])
                                            @if (app()->getLocale() === $locale) aria-current="true" @endif
                                            href="{{ route('locale.switch', $locale) }}"><img class="locale-flag"
                                                src="{{ asset('images/flags/' . $option['flag'] . '.svg') }}"
                                                alt=""><span>{{ $option['label'] }}</span></a></li>
                                @endforeach
                            </ul>
                        </div>
                        @php
                            $cartCount = collect(
                                session(\App\Services\CustomerOrder\CustomerCartService::SESSION_KEY, []),
                            )->sum('quantity');
                        @endphp
                        <div class="mini-cart" data-mini-cart data-endpoint="{{ route('customer.cart.mini') }}"
                            data-items-url="{{ url('/cart/items') }}"
                            data-cart-url="{{ route('customer.cart.index') }}"
                            data-empty-label="{{ __('customer_order.empty') }}"
                            data-subtotal-label="{{ __('customer_order.mini_subtotal') }}"
                            data-checkout-label="{{ __('customer_order.mini_checkout') }}"
                            data-remove-label="{{ __('customer_order.remove') }}"
                            data-floating-title="{{ __('customer_order.mini_floating_title') }}"
                            data-header-title="{{ __('customer_order.mini_title') }}">
                            <button class="nav-link cart-link nav-action" type="button" data-mini-cart-toggle
                                aria-expanded="false" aria-controls="miniCartPanel"><svg viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path
                                        d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7M10 20h.01M18 20h.01" />
                                </svg><span>{{ __('customer_order.cart') }}</span><span class="cart-count"
                                    data-cart-count
                                    aria-label="{{ __('customer_ui.cart_count', ['count' => $cartCount]) }}">{{ $cartCount }}</span></button>
                            <section class="mini-cart-panel" id="miniCartPanel" data-mini-cart-panel
                                aria-label="{{ __('customer_order.mini_title') }}" hidden>
                                <div class="mini-cart-head">
                                    <div><span>{{ __('customer_order.mini_eyebrow') }}</span>
                                        <h2>{{ __('customer_order.mini_title') }}</h2>
                                    </div><button type="button" data-mini-cart-close
                                        aria-label="{{ __('customer_ui.close') }}">×</button>
                                </div>
                                <div class="mini-cart-body" data-mini-cart-body>
                                    <p class="mini-cart-loading">{{ __('customer_order.mini_loading') }}</p>
                                </div>
                            </section>
                            <noscript><a class="visually-hidden-focusable"
                                    href="{{ route('customer.cart.index') }}">{{ __('customer_order.open_cart') }}</a></noscript>
                        </div>
                        @guest
                            <div class="dropdown auth-menu"><button class="btn auth-menu-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false"><svg viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path d="M4 6h16M4 12h16M4 18h16" />
                                    </svg><span class="visually-hidden">{{ __('customer_ui.account') }}</span></button>
                                <ul class="dropdown-menu dropdown-menu-end auth-menu-dropdown">
                                    <li><a class="dropdown-item" href="{{ route('login') }}">{{ __('app.login') }}</a>
                                    </li>
                                    <li><a class="dropdown-item auth-register-link"
                                            href="{{ route('customer.registration.create') }}">{{ __('customer.registration.action') }}</a>
                                    </li>
                                </ul>
                            </div>
                        @else
                            @if (auth()->user()->can('context.pos.access') ||
                                    auth()->user()->can('context.kitchen.access') ||
                                    auth()->user()->can('context.admin.access'))
                                @php
                                    $storeRoute = match (true) {
                                        auth()->user()->can('restaurant-table.manage') => route(
                                            'admin.restaurant-tables.index',
                                        ),
                                        auth()->user()->can('context.pos.access') => route('pos.home'),
                                        default => route('kitchen.home'),
                                    };
                                @endphp
                                <a class="staff-store-entry" href="{{ $storeRoute }}"><span
                                        aria-hidden="true">↗</span> Vào cửa hàng</a>
                            @endif
                            <div class="dropdown"><button class="btn btn-light-outline btn-sm dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">{{ __('customer_ui.account') }}</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('customer.profile.manage-own')
                                        @if (auth()->user()->customer)
                                            <li><a class="dropdown-item"
                                                    href="{{ route('customer.profile.show') }}">{{ __('customer.profile.title') }}</a>
                                            </li>
                                        @endif
                                    @endcan
                                    @can('customer.reservation.view-own')
                                        <li><a class="dropdown-item"
                                                href="{{ route('customer.reservations.index') }}">{{ __('reservation.customer.mine') }}</a>
                                        </li>
                                    @endcan
                                    @can('customer.order.view-own')
                                        <li><a class="dropdown-item"
                                                href="{{ route('customer.orders.history') }}">{{ __('order_history.title') }}</a>
                                        </li>
                                    @endcan
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form method="post" action="{{ route('logout') }}">@csrf<button
                                                class="dropdown-item">{{ __('customer.logout') }}</button></form>
                                    </li>
                                </ul>
                            </div>
                        @endguest
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <nav class="mobile-bottom-nav" aria-label="Điều hướng nhanh">
        <a @class(['mobile-bottom-nav__item', 'active' => request()->routeIs('customer.home')])
            href="{{ route('customer.home') }}"
            @if (request()->routeIs('customer.home')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z" /></svg>
            <span>{{ __('customer_ui.home_navigation') }}</span>
        </a>
        <a @class(['mobile-bottom-nav__item', 'active' => request()->routeIs('customer.posts.*')])
            href="{{ route('customer.posts.index') }}"
            @if (request()->routeIs('customer.posts.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5" /></svg>
            <span>{{ __('customer_ui.news_navigation') }}</span>
        </a>
        <a @class(['mobile-bottom-nav__item', 'mobile-bottom-nav__booking', 'active' => request()->routeIs('customer.reservations.*')])
            href="{{ route('customer.reservations.create') }}"
            @if (request()->routeIs('customer.reservations.*')) aria-current="page" @endif>
            <span class="mobile-bottom-nav__booking-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z" /><path d="m9 15 2 2 4-5" /></svg>
            </span>
            <span>{{ __('customer_ui.reservation_navigation') }}</span>
        </a>
        <a @class(['mobile-bottom-nav__item', 'active' => request()->routeIs('customer.menu.*', 'customer.products.*')])
            href="{{ route('customer.menu.index') }}"
            @if (request()->routeIs('customer.menu.*', 'customer.products.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
            <span>{{ __('customer_ui.menu_navigation') }}</span>
        </a>
        <button @class(['mobile-bottom-nav__item', 'active' => request()->routeIs('customer.cart.*', 'customer.checkout.*')])
            type="button" data-mini-cart-mobile aria-expanded="false" aria-controls="miniCartPanel"
            aria-haspopup="dialog" aria-label="{{ __('customer_order.mini_open') }}">
            <span class="mobile-bottom-nav__cart-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7M10 20h.01M18 20h.01" /></svg>
                <span class="mobile-bottom-nav__count" data-cart-count>{{ $cartCount }}</span>
            </span>
            <span>{{ __('customer_ui.cart_navigation') }}</span>
        </button>
    </nav>
    <button class="floating-cart" type="button" data-mini-cart-floating
        @if ($cartCount < 1) hidden @endif aria-label="{{ __('customer_order.mini_open') }}">
        <span class="floating-cart-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7M10 20h.01M18 20h.01" />
            </svg></span>
        <span class="floating-cart-copy"><small><span data-cart-count>{{ $cartCount }}</span>
                {{ __('customer_order.mini_items') }}</small><strong data-mini-cart-subtotal>—</strong></span>
    </button>
    <div class="mini-cart-backdrop" data-mini-cart-backdrop hidden></div>
    <div class="flash-stack container" aria-live="polite">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="status">
                {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="{{ __('customer_ui.close') }}"></button></div>
            @endif @if ($errors->any())
                <div class="alert alert-danger" role="alert"><strong>{{ $errors->first() }}</strong></div>
            @endif
    </div>
    <main id="main-content" @class([
        'customer-main',
        'customer-main--flush' =>
            trim($__env->yieldContent('page-layout')) === 'flush',
        'customer-main--menu' => request()->routeIs('customer.menu.*'),
        'customer-main--cart' => request()->routeIs('customer.cart.*'),
        'customer-main--product' => request()->routeIs('customer.products.*'),
        'customer-main--form' => request()->routeIs(
            'customer.reservations.create',
            'customer.checkout.*'),
    ])>
        <div class="container">@yield('content')</div>
    </main>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-main">
                <div class="footer-brand">
                    <a class="brand-lockup" href="{{ route('customer.home') }}">
                        <img class="brand-mark" src="{{ $siteLogoUrl }}" alt=""
                            aria-hidden="true">
                        <span class="brand-wordmark">{{ $siteName }}</span>
                    </a>
                    <p>{{ __('customer_ui.footer_summary') }}</p>
                </div>

                <nav class="footer-nav" aria-labelledby="footer-explore-title">
                    <h2 id="footer-explore-title">{{ __('customer_ui.footer_explore') }}</h2>
                    <ul class="footer-links">
                        <li><a href="{{ route('customer.menu.index') }}">{{ __('app.menu.title') }}</a></li>
                        <li><a href="{{ route('customer.posts.index') }}">{{ __('customer_ui.news_navigation') }}</a></li>
                        <li>
                            <a href="{{ route('customer.reservations.create') }}">
                                {{ __('reservation.customer.make') }}
                            </a>
                        </li>
                        <li><a href="{{ route('customer.cart.index') }}">{{ __('customer_order.cart') }}</a></li>
                    </ul>
                </nav>

                <nav class="footer-nav" aria-labelledby="footer-account-title">
                    <h2 id="footer-account-title">{{ __('customer_ui.footer_account') }}</h2>
                    <ul class="footer-links">
                        @guest
                            <li><a href="{{ route('login') }}">{{ __('app.login') }}</a></li>
                            <li><a
                                    href="{{ route('customer.registration.create') }}">{{ __('customer.registration.action') }}</a>
                            </li>
                        @else
                            @can('customer.reservation.view-own')
                                <li><a
                                        href="{{ route('customer.reservations.index') }}">{{ __('reservation.customer.mine') }}</a>
                                </li>
                            @endcan
                            @can('customer.order.view-own')
                                <li><a href="{{ route('customer.orders.history') }}">{{ __('order_history.title') }}</a></li>
                            @endcan
                        @endguest
                    </ul>
                </nav>

                <div class="footer-visit">
                    <h2>{{ __('customer_ui.footer_visit') }}</h2>
                    <p>{{ __('customer_ui.footer_visit_copy') }}</p>
                    <a class="footer-action" href="{{ route('customer.reservations.create') }}">
                        {{ __('reservation.customer.make') }}
                    </a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>{{ __('customer_ui.copyright', ['year' => now()->year]) }}</span>
                <span>{{ __('customer_ui.brand_tagline') }}</span>
            </div>
        </div>
    </footer>
    {!! $publicSettings[\App\Services\SystemSetting\SystemSettingCatalog::CUSTOM_SCRIPT_FOOTER] ?? '' !!}
</body>

</html>
