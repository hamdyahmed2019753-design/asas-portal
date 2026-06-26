@extends('layouts.portal')

@section('title', 'التوزيعات')

@section('content')
    <x-ip.page-header title="التوزيعات" subtitle="جميع توزيعاتك عبر مشاركاتك في أساس." />

    {{-- KPIs --}}
    <div class="ip-grid">
        <x-ip.stat-card color="success" icon="ti-circle-check" label="التوزيعات المدفوعة" :value="$kpis['paid']" />
        <x-ip.stat-card color="warning" icon="ti-alert-circle" label="التوزيعات المستحقة" :value="$kpis['due']" />
        <x-ip.stat-card color="info" icon="ti-calendar" label="التوزيعات القادمة" :value="$kpis['upcoming']" />
        <x-ip.stat-card color="primary" icon="ti-coins" label="إجمالي الأرباح المدفوعة" :value="money($kpis['profitPaid'])" />
    </div>

    {{-- Tabs (query-string) --}}
    @php
        $tabDefs = [['all', 'الكل'], ['paid', 'مدفوعة'], ['due', 'مستحقة'], ['upcoming', 'قادمة']];
        $tabs = collect($tabDefs)->map(fn ($t) => [
            'label' => $t[1],
            'url' => route('portal.payouts', array_merge(request()->only(['contract', 'type', 'year']), ['tab' => $t[0]])),
            'active' => $tab === $t[0],
        ])->all();
    @endphp
    <div style="margin:22px 0 14px;">
        <x-ip.tab-group :tabs="$tabs" :links="true" />
    </div>

    {{-- Filters --}}
    <form method="GET" class="ip-filters">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="ip-field">
            <span class="ip-field__label">العقد</span>
            <select name="contract" class="ip-select" onchange="this.form.submit()">
                <option value="">كل العقود</option>
                @foreach ($contracts as $contract)
                    <option value="{{ $contract->id }}" @selected((string) request('contract') === (string) $contract->id)>{{ $contract->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="ip-field">
            <span class="ip-field__label">النوع</span>
            <select name="type" class="ip-select" onchange="this.form.submit()">
                <option value="">كل الأنواع</option>
                @foreach ($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ip-field">
            <span class="ip-field__label">السنة</span>
            <select name="year" class="ip-select" onchange="this.form.submit()">
                <option value="">كل السنوات</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="ip-btn">تصفية</button>
    </form>

    {{-- Table --}}
    @if ($payouts->isEmpty())
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-calendar-off"
                title="لا توجد توزيعات"
                description="لا توجد توزيعات مطابقة لاختيارك الحالي.">
                <x-slot:action>
                    <a href="{{ route('contracts.index') }}" class="ip-btn">العودة إلى العقود</a>
                </x-slot:action>
            </x-ip.empty-state>
        </x-ip.card>
    @else
        <x-ip.data-table>
            <x-slot:header>
                <tr><th>العقد</th><th>النوع</th><th>المبلغ</th><th>الحالة</th><th>الاستحقاق</th><th>تاريخ الدفع</th></tr>
            </x-slot:header>
            @foreach ($payouts as $payout)
                <tr>
                    <td>{{ $payout->investment?->contract?->title }}</td>
                    <td>{{ $payout->type_label }}</td>
                    <td>{{ $payout->amount !== null ? money($payout->amount) : '—' }}</td>
                    <td><x-ip.status-pill :color="$payout->status_color" :label="$payout->status_label" /></td>
                    <td>{{ $payout->due_date?->format('Y-m-d') }}</td>
                    <td>{{ $payout->paid_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @endforeach
        </x-ip.data-table>
    @endif
@endsection
