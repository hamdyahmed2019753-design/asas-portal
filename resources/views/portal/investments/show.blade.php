@extends('layouts.portal')

@section('title', 'تفاصيل المشاركة')

@section('content')
    @php $return = $expectedReturn !== null ? rtrim(rtrim($expectedReturn, '0'), '.').'%' : '—'; @endphp

    <x-ip.page-header title="تفاصيل المشاركة" :subtitle="$investment->contract?->title">
        <x-slot:actions>
            <a href="{{ route('portal.investments') }}" style="color: var(--ip-muted); font-size:13px;">→ كل المشاركات</a>
        </x-slot:actions>
    </x-ip.page-header>

    {{-- Hero --}}
    <x-ip.hero-balance
        title="المبلغ المستثمَر"
        :value="money($investedAmount)"
        :description="'إجمالي الأرباح المحققة: '.money($profitPaid).' · العائد المتوقع: '.$return" />

    {{-- Summary cards --}}
    <x-ip.section-header title="ملخّص التوزيعات" />
    <div class="ip-grid">
        <x-ip.stat-card color="primary" icon="ti-list-numbers" label="عدد التوزيعات" :value="$summary['total']" />
        <x-ip.stat-card color="success" icon="ti-circle-check" label="المدفوعة" :value="$summary['paid']" />
        <x-ip.stat-card color="warning" icon="ti-alert-circle" label="المستحقة" :value="$summary['due']" />
        <x-ip.stat-card color="info" icon="ti-calendar" label="القادمة" :value="$summary['upcoming']" />
    </div>

    {{-- Payouts table --}}
    <x-ip.section-header title="جدول التوزيعات" />
    @if ($hasPayouts)
        <x-ip.data-table>
            <x-slot:header>
                <tr><th>النوع</th><th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th><th>تاريخ الدفع</th></tr>
            </x-slot:header>
            @foreach ($payouts as $payout)
                <tr>
                    <td>{{ $payout->type_label }}</td>
                    <td>{{ $payout->amount !== null ? money($payout->amount) : '—' }}</td>
                    <td>{{ $payout->due_date?->format('Y-m-d') }}</td>
                    <td><x-ip.status-pill :color="$payout->status_color" :label="$payout->status_label" /></td>
                    <td>{{ $payout->paid_at?->format('Y-m-d') ?? '—' }}</td>
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
@endsection
