@extends('pdf.layout')

@section('title', 'إيصال دفعة')

@section('content')
    <p>تؤكد منصة {{ $brand['siteName'] }} صرف الدفعة التالية للمستثمر:</p>

    <table class="kv">
        <tr><td class="k">المستثمر</td><td class="v">{{ $payout->investment->user->name }}</td></tr>
        <tr><td class="k">العقد</td><td class="v">{{ $payout->investment->contract?->title }}</td></tr>
        <tr><td class="k">نوع الدفعة</td><td class="v">{{ $payout->type_label }}</td></tr>
        <tr><td class="k">تاريخ الاستحقاق</td><td class="v">{{ $payout->due_date?->translatedFormat('d F Y') ?? '—' }}</td></tr>
        <tr><td class="k">تاريخ الصرف</td><td class="v">{{ $payout->paid_at?->translatedFormat('d F Y') ?? '—' }}</td></tr>
    </table>

    <p class="center big" style="margin-top:18px;">{{ money($payout->amount) }}</p>

    <div class="seal">إيصال دفعة رقم #{{ $payout->id }} — معتمد إلكترونيًا</div>
@endsection
