@extends('layouts.portal')

@section('title', 'العقود الاستثمارية')
@section('meta_description', 'تصفّح العقود الاستثمارية المتاحة في أساس — عوائد واضحة وجداول توزيعات محددة.')

@section('content')
    <x-ip.page-header title="العقود الاستثمارية" subtitle="استعرض الفرص المتاحة وفلترها حسب الحالة ونوع النشاط." />

    <form method="GET" class="ip-filters">
        <div class="ip-field">
            <span class="ip-field__label">الحالة</span>
            <select name="status" class="ip-select" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ip-field">
            <span class="ip-field__label">نوع النشاط</span>
            <select name="activity_type" class="ip-select" onchange="this.form.submit()">
                <option value="">كل الأنشطة</option>
                @foreach ($activityTypes as $type)
                    <option value="{{ $type }}" @selected(request('activity_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="ip-btn">تصفية</button>
    </form>

    @forelse ($contracts as $contract)
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
            <x-ip.empty-state icon="ti-folder-search" title="لا توجد عقود مطابقة" description="جرّب تغيير الفلاتر أو عُد لاحقًا لاستكشاف فرص جديدة." />
        </x-ip.card>
    @endforelse

    <div class="ip-pagination">
        {{ $contracts->onEachSide(1)->links() }}
    </div>
@endsection
