<!DOCTYPE html>
@php($appLocale = setting('general.default_language', 'ar'))
<html lang="{{ $appLocale }}" dir="{{ $appLocale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة المستثمر') · {{ setting('general.site_name', 'أساس') }}</title>
    @include('partials.favicon')
    @include('partials.seo')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Self-hosted Tabler icon webfont + preload: glyphs render on first paint
         instead of waiting for a CDN CSS→font chain that left icons blank. --}}
    <link rel="preload" as="font" type="font/woff2" href="/tabler/fonts/tabler-icons.woff2?v3.7.0" crossorigin>
    <link href="/tabler/tabler-icons.min.css" rel="stylesheet">

    <script>
        (function () {
            try {
                if (localStorage.ipTheme === 'dark' || (!('ipTheme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/css/portal/theme.css', 'resources/js/app.js'])
</head>
<body class="ip-shell"
      x-data="{ drawer: false, dark: document.documentElement.classList.contains('dark'),
                toggleDark() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.ipTheme = this.dark ? 'dark' : 'light'; } }">

{{-- Navigation loading indicators (top progress bar + branded spinner) --}}
<div class="ip-loadbar" id="ipLoadbar"><span class="ip-loadbar__fill"></span></div>
<div class="ip-loader" id="ipLoader" aria-hidden="true">
    <div class="ip-loader__mark">أ<span class="ip-loader__ring"></span></div>
</div>

<header class="ip-header">
    <div class="ip-header__inner">
        <a class="ip-logo" href="{{ auth()->check() ? route('portal.dashboard') : route('home') }}">
            @include('partials.brand')
        </a>

        <nav class="ip-nav">
            @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}" @class(['is-active' => request()->routeIs($item['route'])])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="ip-header__actions">
            <button type="button" class="ip-iconbtn" @click="toggleDark()" aria-label="تبديل الوضع الداكن">
                <i class="ti" :class="dark ? 'ti-sun' : 'ti-moon'"></i>
            </button>

            @auth
                <a href="{{ route('portal.notifications') }}" class="ip-iconbtn ip-bell" aria-label="الإشعارات">
                    <i class="ti ti-bell"></i>
                    @if (($navUnreadCount ?? 0) > 0)
                        <span class="ip-bell__badge">{{ $navUnreadCount > 9 ? '9+' : $navUnreadCount }}</span>
                    @endif
                </a>
                <div x-data="{ open: false }" style="position:relative;">
                    <button type="button" class="ip-avatar" @click="open = !open" aria-label="حسابي"><i class="ti ti-user"></i></button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         style="position:absolute; inset-inline-start:0; top:38px; background:var(--ip-card-bg); border:1px solid var(--ip-border); border-radius:12px; box-shadow:var(--ip-shadow-lg); padding:6px; min-width:180px; z-index:50;">
                        <a href="{{ route('portal.profile') }}" style="display:block; padding:9px 12px; font-size:13px; color:var(--ip-text); text-decoration:none; border-radius:8px;">الملف الشخصي</a>
                        <a href="{{ route('portal.settings') }}" style="display:block; padding:9px 12px; font-size:13px; color:var(--ip-text); text-decoration:none; border-radius:8px;">الإعدادات والأمان</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" style="width:100%; text-align:start; padding:9px 12px; font-size:13px; color:var(--ip-danger-700); background:none; border:0; cursor:pointer;">تسجيل الخروج</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="ip-btn">دخول</a>
            @endauth

            <button type="button" class="ip-iconbtn ip-hamburger" @click="drawer = true" aria-label="القائمة"><i class="ti ti-menu-2"></i></button>
        </div>
    </div>
</header>

<template x-if="drawer">
    <div>
        <div class="ip-drawer-backdrop" @click="drawer = false"></div>
        <aside class="ip-drawer">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span class="ip-logo">@include('partials.brand')</span>
                <button type="button" class="ip-iconbtn" @click="drawer = false" aria-label="إغلاق"><i class="ti ti-x"></i></button>
            </div>
            @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}" @class(['is-active' => request()->routeIs($item['route'])])>{{ $item['label'] }}</a>
            @endforeach
        </aside>
    </div>
</template>

<main class="ip-container">
    @yield('content', $slot ?? '')
</main>

<footer class="ip-footer">
    © {{ date('Y') }} {{ setting('general.site_name', 'أساس') }} — بوابة المستثمرين. جميع الحقوق محفوظة.

    @if ($supportEmail || $supportPhone || $whatsapp)
        <span class="ip-footer__support">
            @if ($supportEmail) · <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> @endif
            @if ($supportPhone) · <a href="tel:{{ $supportPhone }}">{{ $supportPhone }}</a> @endif
            @if ($whatsapp) · <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener">واتساب</a> @endif
        </span>
    @endif

    @if ($address || $mapsUrl)
        <div class="ip-footer__row">
            @if ($address) {{ $address }} @endif
            @if ($mapsUrl) · <a href="{{ $mapsUrl }}" target="_blank" rel="noopener">عرض على الخريطة</a> @endif
        </div>
    @endif



    @include('partials.social-links')
</footer>

<style>[x-cloak]{display:none!important;}</style>

{{-- Page-transition loader: instant visual feedback so navigation never feels stuck. --}}
<script>
    (function () {
        var bar = document.getElementById('ipLoadbar');
        var loader = document.getElementById('ipLoader');
        var timer = null;
        function start() {
            if (bar) bar.classList.add('is-active');
            // Show the branded spinner only if the next page takes a moment.
            timer = setTimeout(function () { if (loader) loader.classList.add('is-active'); }, 180);
        }
        function stop() {
            clearTimeout(timer);
            if (bar) bar.classList.remove('is-active');
            if (loader) loader.classList.remove('is-active');
        }
        // Internal link navigations.
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download')) return;
            if (href.indexOf('javascript:') === 0 || a.hasAttribute('x-on:click') || a.hasAttribute('@click')) return;
            if (a.origin && a.origin !== location.origin) return;
            start();
        }, true);
        // Form submissions (filters, interest, settings…).
        document.addEventListener('submit', function () { start(); }, true);
        // Reset when the new page is shown (covers back/forward bfcache).
        window.addEventListener('pageshow', stop);
        window.addEventListener('beforeunload', start);
    })();
</script>
@stack('scripts')
</body>
</html>
