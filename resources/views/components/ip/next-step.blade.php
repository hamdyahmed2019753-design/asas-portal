@props(['step'])

{{-- Single, prominent "next action" hero — one clear CTA for the investor. --}}
<div class="ip-banner ip-banner--{{ $step['tone'] ?? 'info' }}" style="justify-content:space-between; align-items:center; gap:16px;">
    <span style="display:flex; align-items:flex-start; gap:12px;">
        <span class="ip-banner__icon"><i class="ti {{ $step['icon'] ?? 'ti-arrow-left' }}"></i></span>
        <span style="display:flex; flex-direction:column; gap:3px;">
            <strong style="font-size:11px; font-weight:700; opacity:.7; letter-spacing:.5px;">خطوتك التالية</strong>
            <strong style="font-size:15px;">{{ $step['title'] }}</strong>
            <span style="font-size:13px; opacity:.92;">{{ $step['body'] }}</span>
        </span>
    </span>
    @if (! empty($step['url']))
        <a href="{{ $step['url'] }}" class="ip-btn" style="white-space:nowrap;">{{ $step['cta'] }}</a>
    @endif
</div>
