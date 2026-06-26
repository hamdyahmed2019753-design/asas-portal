{{--
    Social media links — renders an icon anchor for every platform that has a
    URL stored in Settings. Empty settings are never rendered, so the whole
    block is omitted when no social link is configured. URLs come exclusively
    from Settings (social.*), never hardcoded.
--}}
@php
    $platforms = [
        'social.facebook'  => ['ti-brand-facebook',  'Facebook'],
        'social.twitter'   => ['ti-brand-x',         'X'],
        'social.instagram' => ['ti-brand-instagram', 'Instagram'],
        'social.linkedin'  => ['ti-brand-linkedin',  'LinkedIn'],
        'social.youtube'   => ['ti-brand-youtube',   'YouTube'],
        'social.snapchat'  => ['ti-brand-snapchat',  'Snapchat'],
        'social.tiktok'    => ['ti-brand-tiktok',    'TikTok'],
    ];

    $links = [];
    foreach ($platforms as $key => [$icon, $label]) {
        if (filled($url = setting($key))) {
            $links[] = ['url' => $url, 'icon' => $icon, 'label' => $label];
        }
    }
@endphp

@if ($links)
    <div class="ip-footer__social">
        @foreach ($links as $link)
            <a class="ip-social-link" href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow" aria-label="{{ $link['label'] }}">
                <i class="ti {{ $link['icon'] }}" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif
