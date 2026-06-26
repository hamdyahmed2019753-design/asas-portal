{{--
    SEO meta partial — single source for description / keywords / canonical /
    Open Graph / Twitter card across every layout.

    Priority for each value (never overrides a value the page itself provides):
        1. Blade section set by the page  (@section('title' | 'meta_description' |
                                              'meta_keywords' | 'canonical' | 'og_image'))
        2. Settings                       (seo.* / general.*)
        3. Safe fallback

    The <title> tag itself stays in each layout; this partial owns everything
    else. No SEO package — pure Blade. Tags whose value is empty are omitted.
--}}
@php
    $siteName = setting('general.site_name', 'أساس');
    $siteDesc = setting('seo.meta_description', setting('general.site_description', 'بوابة مستثمري أساس — فرص استثمارية مدروسة بعوائد واضحة وجداول توزيعات محددة.'));

    $pageTitle     = $__env->hasSection('title')            ? trim((string) $__env->yieldContent('title'))            : null;
    $pageDesc      = $__env->hasSection('meta_description') ? trim((string) $__env->yieldContent('meta_description')) : null;
    $pageKeywords  = $__env->hasSection('meta_keywords')    ? trim((string) $__env->yieldContent('meta_keywords'))    : null;
    $pageCanonical = $__env->hasSection('canonical')        ? trim((string) $__env->yieldContent('canonical'))        : null;
    $pageOgImage   = $__env->hasSection('og_image')         ? trim((string) $__env->yieldContent('og_image'))         : null;

    $title     = $pageTitle     ?: setting('seo.meta_title', $siteName);
    $desc      = $pageDesc      ?: $siteDesc;
    $keywords  = $pageKeywords  ?: setting('seo.meta_keywords');
    $ogImage   = $pageOgImage   ?: setting_file_url('seo.og_image');
    $canonical = $pageCanonical ?: (setting('general.website_url') ?: rtrim((string) config('app.url'), '/'));
    $canonical = filled($canonical) ? rtrim($canonical, '/') : null;
@endphp

<meta name="description" content="{{ $desc }}">
@if (filled($keywords))
    <meta name="keywords" content="{{ $keywords }}">
@endif
@if (filled($canonical))
    <link rel="canonical" href="{{ $canonical }}">
@endif

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $desc }}">
@if (filled($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif
@if (filled($canonical))
    <meta property="og:url" content="{{ $canonical }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $desc }}">
@if (filled($ogImage))
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
