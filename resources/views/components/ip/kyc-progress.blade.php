@props([
    'kyc' => [],
])

@php($state = $kyc['state'] ?? null)

@if ($state)
    <x-ip.card>
        <div class="ip-kyc__head" style="font-size:15px; color:var(--ip-text); font-weight:600;">
            <span>التحقق من هويتك</span>
            <x-ip.status-pill :color="$state->color()" :label="$state->label()" />
        </div>

        <div class="ip-wizard__bar" style="margin:4px 0 12px;"><span class="ip-wizard__fill" style="width: {{ $kyc['progress'] }}%;"></span></div>

        <p class="ip-note" style="margin:0 0 14px;">{{ $state->message() }}</p>

        @if (! empty($kyc['canResubmit']))
            <a href="{{ route('portal.kyc.resubmit') }}" class="ip-btn"><i class="ti ti-upload"></i> إعادة رفع المستندات</a>
        @else
            <a href="{{ route('portal.profile') }}" class="ip-btn">عرض حالة التحقق</a>
        @endif
    </x-ip.card>
@endif
