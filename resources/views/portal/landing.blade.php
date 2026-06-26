@extends('layouts.portal')

@section('title', 'استثمر بثقة مع أساس')
@section('meta_description', 'بوابة مستثمري أساس — فرص استثمارية مدروسة بعوائد واضحة وجداول توزيعات محددة.')

@section('content')
    {{-- Hero --}}
    <x-ip.hero-balance
        title="منصة أساس للاستثمار"
        value="استثمر بثقة مع أساس"
        description="فرص استثمارية مدروسة بعوائد واضحة وجداول توزيعات محددة.">
        <x-slot:cta>
            <a href="{{ route('contracts.index') }}" class="ip-btn" style="background: var(--ip-on-accent); color: var(--ip-primary-700);">
                استعرض العقود
            </a>
        </x-slot:cta>
    </x-ip.hero-balance>

    {{-- Statistics strip --}}
    <x-ip.section-header title="أساس بالأرقام" />
    <div class="ip-grid ip-grid--3">
        <x-ip.stat-card color="success" icon="ti-folder-open" label="العقود المفتوحة" :value="$stats['openContracts']" />
        <x-ip.stat-card color="info" icon="ti-users" label="المستثمرون" :value="$stats['investors']" />
        <x-ip.stat-card color="primary" icon="ti-coins" label="إجمالي الاستثمارات" :value="money($stats['invested'])" />
    </div>

    {{-- Featured contracts --}}
    <x-ip.section-header title="عقود مميّزة" subtitle="أحدث الفرص الاستثمارية المتاحة">
        <x-slot:action><a href="{{ route('contracts.index') }}" style="color: var(--ip-primary-600);">عرض الكل</a></x-slot:action>
    </x-ip.section-header>

    @forelse ($featured as $contract)
        @php $return = config('app.show_public_returns') && $contract->expected_return !== null
            ? rtrim(rtrim($contract->expected_return, '0'), '.').'%'
            : 'عند الطلب'; @endphp
        @if ($loop->first)<div class="ip-grid">@endif
            <x-ip.contract-card
                :title="$contract->title"
                :activity="$contract->activity_type"
                :target-amount="money($contract->target_amount)"
                :expected-return="$return"
                :duration="$contract->duration_months.' شهرًا'"
                :status-label="$contract->status_label"
                :status-color="$contract->status_color">
                <x-slot:cta>
                    <a href="{{ route('contracts.show', $contract) }}" class="ip-btn">عرض التفاصيل</a>
                </x-slot:cta>
            </x-ip.contract-card>
        @if ($loop->last)</div>@endif
    @empty
        <x-ip.card>
            <x-ip.empty-state icon="ti-folder-open" title="لا توجد عقود متاحة حاليًا" description="تابعنا — سيتم طرح فرص استثمارية جديدة قريبًا." />
        </x-ip.card>
    @endforelse

    {{-- Latest news --}}
    <x-ip.section-header title="الجديد في أساس" />
    @if ($news->isNotEmpty())
        <x-ip.card>
            @foreach ($news as $item)
                <x-ip.news-item
                    :title="$item->title"
                    :excerpt="\Illuminate\Support\Str::limit($item->body, 120)"
                    :published-date="$item->published_at?->format('Y-m-d')" />
            @endforeach
        </x-ip.card>
    @else
        <x-ip.card>
            <x-ip.empty-state icon="ti-news" title="لا توجد أخبار بعد" description="ستظهر هنا آخر تحديثات أساس." />
        </x-ip.card>
    @endif
@endsection
