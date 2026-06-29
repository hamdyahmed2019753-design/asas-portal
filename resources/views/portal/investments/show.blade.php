@extends('layouts.portal')

@section('title', 'تفاصيل المشاركة')

@section('content')
    @php
        $contract = $investment->contract;
        $return = $expectedReturn !== null ? rtrim(rtrim($expectedReturn, '0'), '.').'%' : '—';
        $fmt = fn ($d) => $d?->translatedFormat('d F Y') ?? '—';
    @endphp

    <x-ip.page-header title="تفاصيل المشاركة" :subtitle="$contract?->title">
        <x-slot:actions>
            <a href="{{ route('portal.investments') }}" style="color: var(--ip-muted); font-size:13px;">→ كل المشاركات</a>
        </x-slot:actions>
    </x-ip.page-header>

    {{-- Hero --}}
    <x-ip.hero-balance
        title="المبلغ المستثمَر"
        :value="money($investedAmount)"
        :description="'الأرباح المحققة: '.money($profit['received']).' · العائد المتوقع: '.$return" />

    {{-- Quick actions --}}
    <div style="display:flex; flex-wrap:wrap; gap:10px; margin:14px 0;">
        <a href="{{ route('portal.payouts') }}" class="ip-btn"><i class="ti ti-list-check"></i> عرض جميع الدفعات</a>
        @if (! empty($support['email']))
            <a href="mailto:{{ $support['email'] }}?subject={{ rawurlencode('استفسار عن مشاركة #'.$investment->id) }}" class="ip-btn" style="background:transparent; color:var(--ip-primary); border:1px solid var(--ip-border);"><i class="ti ti-headset"></i> التواصل مع الدعم</a>
        @endif
        <a href="{{ route('portal.investments.contract', $investment) }}" target="_blank" class="ip-btn"><i class="ti ti-file-download"></i> تحميل العقد</a>
        <a href="{{ route('portal.investments.statement', $investment) }}" target="_blank" class="ip-btn"><i class="ti ti-file-spreadsheet"></i> تحميل كشف الحساب</a>
    </div>

    {{-- Investment summary --}}
    <x-ip.section-header title="ملخص الاستثمار" />
    <x-ip.card>
        <div class="ip-kv">
            <div class="ip-kv__row"><span class="ip-kv__label">قيمة الاستثمار</span><span class="ip-kv__value">{{ money($investedAmount) }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">الحالة</span><span class="ip-kv__value"><x-ip.status-pill :color="$investment->status_color" :label="$investment->status_label" /></span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">تاريخ البداية</span><span class="ip-kv__value">{{ $fmt($investment->start_date) }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">تاريخ النهاية</span><span class="ip-kv__value">{{ $fmt($investment->end_date) }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">نسبة العائد</span><span class="ip-kv__value">{{ $return }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">مدة العقد</span><span class="ip-kv__value">{{ $contract?->duration_months ? $contract->duration_months.' شهرًا' : '—' }}</span></div>
        </div>
    </x-ip.card>

    {{-- Profits --}}
    <x-ip.section-header title="الأرباح" />
    <div class="ip-grid">
        <x-ip.stat-card color="success" icon="ti-cash" label="الأرباح المستلمة" :value="money($profit['received'])" />
        <x-ip.stat-card color="warning" icon="ti-clock-dollar" label="الأرباح المتبقية" :value="money($profit['remaining'])" />
        <x-ip.stat-card color="info" icon="ti-target" label="إجمالي الأرباح المتوقعة" :value="money($profit['expected'])" />
        <x-ip.stat-card color="primary" icon="ti-coins" label="قيمة المحفظة لهذا الاستثمار" :value="money($profit['value'])" />
    </div>

    {{-- Payouts table --}}
    <x-ip.section-header title="جدول الدفعات" />
    @if ($hasPayouts)
        <x-ip.data-table>
            <x-slot:header>
                <tr><th>النوع</th><th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th><th>تاريخ الدفع</th><th>الإيصال</th></tr>
            </x-slot:header>
            @foreach ($payouts as $payout)
                <tr>
                    <td>{{ $payout->type_label }}</td>
                    <td>{{ $payout->amount !== null ? money($payout->amount) : '—' }}</td>
                    <td>{{ $payout->due_date?->format('Y-m-d') }}</td>
                    <td><x-ip.status-pill :color="$payout->status_color" :label="$payout->status_label" /></td>
                    <td>{{ $payout->paid_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        @if ($payout->status->value === 'paid')
                            <a href="{{ route('portal.payouts.receipt', $payout) }}" target="_blank" class="ip-link" title="تحميل الإيصال"><i class="ti ti-download"></i></a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ip.data-table>
    @else
        <x-ip.card>
            <x-ip.empty-state icon="ti-calendar-off" title="لا توجد توزيعات بعد" description="تُولَّد التوزيعات تلقائيًا عند اعتماد المشاركة." />
        </x-ip.card>
    @endif

    {{-- Timeline --}}
    <x-ip.section-header title="سجل المشاركة" />
    <x-ip.card>
        <x-ip.timeline>
            @foreach ($timeline as $event)
                <x-ip.timeline-item :color="$event['color']" :title="$event['title']" :date="$event['date']" />
            @endforeach
        </x-ip.timeline>
    </x-ip.card>

    {{-- Contract details --}}
    @if ($contract)
        <x-ip.section-header title="بيانات العقد">
            <x-slot:action><a href="{{ route('contracts.show', $contract) }}" style="color: var(--ip-primary-600);">عرض صفحة العقد ←</a></x-slot:action>
        </x-ip.section-header>
        <x-ip.card>
            <div class="ip-kv">
                <div class="ip-kv__row"><span class="ip-kv__label">اسم العقد</span><span class="ip-kv__value">{{ $contract->title }}</span></div>
                <div class="ip-kv__row"><span class="ip-kv__label">النشاط</span><span class="ip-kv__value">{{ $contract->activity_type ?? '—' }}</span></div>
                <div class="ip-kv__row"><span class="ip-kv__label">مدة العقد</span><span class="ip-kv__value">{{ $contract->duration_months ? $contract->duration_months.' شهرًا' : '—' }}</span></div>
                <div class="ip-kv__row"><span class="ip-kv__label">العائد المتوقع</span><span class="ip-kv__value">{{ $return }}</span></div>
                <div class="ip-kv__row"><span class="ip-kv__label">حالة العقد</span><span class="ip-kv__value">{{ $contract->status?->label() ?? '—' }}</span></div>
            </div>
        </x-ip.card>
    @endif

    {{-- Documents --}}
    <x-ip.section-header title="المستندات" />
    <x-ip.card>
        @php
            $docs = [
                ['label' => 'عقد الاستثمار', 'icon' => 'ti-file-text', 'url' => route('portal.investments.contract', $investment)],
                ['label' => 'شهادة الاستثمار', 'icon' => 'ti-certificate', 'url' => route('portal.investments.certificate', $investment)],
                ['label' => 'كشف الحساب', 'icon' => 'ti-file-spreadsheet', 'url' => route('portal.investments.statement', $investment)],
            ];
        @endphp
        @foreach ($docs as $doc)
            <div class="ip-kv__row" style="align-items:center;">
                <span class="ip-kv__label" style="display:flex; align-items:center; gap:8px;"><i class="ti {{ $doc['icon'] }}"></i> {{ $doc['label'] }}</span>
                <span class="ip-kv__value"><a href="{{ $doc['url'] }}" target="_blank" class="ip-link"><i class="ti ti-download"></i> تحميل PDF</a></span>
            </div>
        @endforeach
        <div class="ip-kv__row" style="align-items:center;">
            <span class="ip-kv__label" style="display:flex; align-items:center; gap:8px;"><i class="ti ti-receipt"></i> إيصالات الدفعات</span>
            <span class="ip-kv__value muted">متاحة لكل دفعة مدفوعة في جدول الدفعات أعلاه</span>
        </div>
    </x-ip.card>
@endsection
