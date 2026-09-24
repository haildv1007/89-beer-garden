<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@php
    $brandSettings = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->publicSiteSettings();
    $siteName = $brandSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_NAME] ?? 'Beer Garden';
    $brandLogoPath = $brandSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_LOGO] ?? null;
    $siteLogoUrl = $brandLogoPath ? Storage::disk('public')->url($brandLogoPath) : asset('images/brand/quan-89-logo.png');
    $declaredTitle = trim($__env->yieldContent('title'));
    $defaultSuffix = ' - ' . __('app.name');
    $documentTitle = str_ends_with($declaredTitle, $defaultSuffix)
        ? substr($declaredTitle, 0, -strlen($defaultSuffix)) . ' - ' . $siteName
        : ($declaredTitle ?: $siteName);
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle }}</title>
    @vite(['resources/css/app.css', 'resources/css/kitchen.css', 'resources/js/app.js'])
</head>

<body class="kds-shell">
    <header class="kds-header">
        <a class="kds-brand" href="{{ route('kitchen.home') }}"><img class="kds-brand__mark"
                src="{{ $siteLogoUrl }}" alt="" aria-hidden="true"><span><strong>Phiếu
                    bếp</strong><small>{{ $siteName }}</small></span></a>
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
