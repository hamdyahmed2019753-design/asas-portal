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

    {{-- Self-hosted Tabler icon webfont + preload (icons on first paint). --}}
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
<body class="ip-shell ip-auth">
    <div class="ip-auth__split">
        {{-- Form side (always visible) --}}
        <main class="ip-auth__form">
            <div class="ip-auth__form-inner">
                @yield('content')
            </div>
        </main>

        {{-- Marketing hero (desktop only) --}}
        <aside class="ip-auth__hero" aria-hidden="true">
            <div class="ip-auth-hero">
                <a class="ip-logo ip-auth-hero__logo" href="{{ route('home') }}">
                    @include('partials.brand')
                </a>
                <h2 class="ip-auth-hero__headline">بوابتك إلى استثمار أوضح وأذكى.</h2>
                <p class="ip-auth-hero__lead">كل ما تحتاجه لإدارة محفظتك ومتابعة عوائدك في مكان واحد.</p>

                <ul class="ip-auth-hero__list">
                    <li><span class="ip-auth-hero__ico"><i class="ti ti-briefcase"></i></span> إدارة استثماراتك بسهولة</li>
                    <li><span class="ip-auth-hero__ico"><i class="ti ti-coins"></i></span> متابعة التوزيعات</li>
                    <li><span class="ip-auth-hero__ico"><i class="ti ti-bell-ringing"></i></span> إشعارات فورية</li>
                    <li><span class="ip-auth-hero__ico"><i class="ti ti-layout-dashboard"></i></span> لوحة تحكم متكاملة</li>
                </ul>
            </div>
        </aside>
    </div>

    @stack('scripts')
</body>
</html>
