@extends('pdf.layout')

@section('title', 'كشف حساب المشاركة')

@section('content')
    <table class="kv">
        <tr><td class="k">المستثمر</td><td class="v">{{ $investment->user->name }}</td></tr>
        <tr><td class="k">العقد</td><td class="v">{{ $investment->contract?->title }}</td></tr>
        <tr><td class="k">قيمة المشاركة</td><td class="v">{{ money($investment->amount) }}</td></tr>
        <tr><td class="k">الأرباح المستلمة</td><td class="v">{{ money($profit['received']) }}</td></tr>
        <tr><td class="k">الأرباح المتبقية</td><td class="v">{{ money($profit['remaining']) }}</td></tr>
        <tr><td class="k">قيمة المحفظة لهذا الاستثمار</td><td class="v">{{ money($profit['value']) }}</td></tr>
    </table>

    <h4>سجل الدفعات</h4>
    <table>
        <thead>
            <tr><th>#</th><th>النوع</th><th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th><th>تاريخ الدفع</th></tr>
        </thead>
        <tbody>
            @forelse ($payouts as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->type_label }}</td>
                    <td>{{ $p->amount !== null ? money($p->amount) : '—' }}</td>
                    <td>{{ $p->due_date?->format('Y-m-d') }}</td>
                    <td>{{ $p->status_label }}</td>
                    <td>{{ $p->paid_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center muted">لا توجد دفعات بعد.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
