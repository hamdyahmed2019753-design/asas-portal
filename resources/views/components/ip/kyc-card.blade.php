@props([
    'kyc' => [],
])

@php($state = $kyc['state'] ?? null)

<x-ip.card>
    @if ($state)
        {{-- Status banner --}}
        <div class="ip-banner ip-banner--{{ $state->color() }}" style="margin-bottom:18px;">
            <span class="ip-banner__icon"><i class="ti {{ $state->icon() }}"></i></span>
            <span>{{ $state->message() }}</span>
        </div>

        {{-- Progress --}}
        <div class="ip-kyc__progress">
            <div class="ip-kyc__head">
                <span>حالة التحقق</span>
                <x-ip.status-pill :color="$state->color()" :label="$state->label()" />
            </div>
            <div class="ip-wizard__bar"><span class="ip-wizard__fill" style="width: {{ $kyc['progress'] }}%;"></span></div>
        </div>

        {{-- Rejection reason + resubmission --}}
        @if (! empty($kyc['rejectionReason']))
            <div class="ip-banner ip-banner--danger" style="margin:16px 0 0;">
                <span class="ip-banner__icon"><i class="ti ti-message-report"></i></span>
                <span><strong>سبب الرفض:</strong> {{ $kyc['rejectionReason'] }}</span>
            </div>
        @endif

        @if (! empty($kyc['canResubmit']))
            <div style="margin-top:14px;">
                <a href="{{ route('portal.kyc.resubmit') }}" class="ip-btn"><i class="ti ti-upload"></i> إعادة رفع المستندات</a>
            </div>
        @endif

        {{-- Dates --}}
        <div class="ip-kv" style="margin-top:16px;">
            <div class="ip-kv__row"><span class="ip-kv__label">تاريخ رفع المستندات</span><span class="ip-kv__value">{{ $kyc['submittedAt']?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">تاريخ المراجعة</span><span class="ip-kv__value">{{ $kyc['reviewedAt']?->format('Y-m-d') ?? '—' }}</span></div>
        </div>

        {{-- Timeline --}}
        <div style="margin-top:18px;">
            <x-ip.timeline>
                @foreach ($kyc['timeline'] as $event)
                    <x-ip.timeline-item :title="$event['title']" :date="$event['date']" :color="$event['color']" />
                @endforeach
            </x-ip.timeline>
        </div>

        {{-- Documents (signed links) --}}
        @if (! empty($kyc['documents']))
            <div class="ip-kyc__docs">
                <div class="ip-kv__label" style="margin-bottom:10px;">المستندات المرفوعة</div>
                @foreach ($kyc['documents'] as $doc)
                    <a class="ip-kyc__doc" href="{{ $doc['url'] }}">
                        <span><i class="ti ti-file-text"></i> {{ $doc['label'] }}</span>
                        <span class="ip-link"><i class="ti ti-download"></i> تحميل</span>
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <x-ip.empty-state
            icon="ti-id-badge-2"
            title="لم تُرفع مستنداتك بعد"
            description="أكمل خطوات التسجيل ورفع المستندات لبدء عملية التحقق.">
            <x-slot:action>
                <a href="{{ route('portal.onboarding') }}" class="ip-btn">إكمال التسجيل</a>
            </x-slot:action>
        </x-ip.empty-state>
    @endif
</x-ip.card>
