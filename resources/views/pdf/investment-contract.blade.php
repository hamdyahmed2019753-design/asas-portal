@extends('pdf.layout')

@section('title', 'عقد مشاركة استثمارية')

@section('content')
    <p>تم إبرام عقد المشاركة الاستثمارية التالي بين منصة {{ $brand['siteName'] }} والمستثمر المذكور أدناه:</p>

    <table class="kv">
        <tr><td class="k">اسم المستثمر</td><td class="v">{{ $investment->user->name }}</td></tr>
        <tr><td class="k">العقد</td><td class="v">{{ $investment->contract?->title }}</td></tr>
        <tr><td class="k">نوع النشاط</td><td class="v">{{ $investment->contract?->activity_type ?? '—' }}</td></tr>
        <tr><td class="k">قيمة المشاركة</td><td class="v">{{ money($investment->amount) }}</td></tr>
        <tr><td class="k">العائد المتوقع</td><td class="v">{{ $return }}</td></tr>
        <tr><td class="k">مدة العقد</td><td class="v">{{ $investment->contract?->duration_months ? $investment->contract->duration_months.' شهرًا' : '—' }}</td></tr>
        <tr><td class="k">تاريخ البداية</td><td class="v">{{ $investment->start_date?->translatedFormat('d F Y') ?? '—' }}</td></tr>
        <tr><td class="k">تاريخ النهاية</td><td class="v">{{ $investment->end_date?->translatedFormat('d F Y') ?? '—' }}</td></tr>
        <tr><td class="k">حالة المشاركة</td><td class="v">{{ $investment->status_label }}</td></tr>
    </table>

    @if ($investment->contract?->description)
        <p class="muted">{{ $investment->contract->description }}</p>
    @endif

    <p class="muted">يُقرّ المستثمر بموافقته على شروط المشاركة في هذا العقد، وأن العوائد متوقعة وغير مضمونة وتخضع لأداء النشاط الاستثماري.</p>

    <div class="seal">وثيقة مشاركة رقم #{{ $investment->id }} — معتمدة إلكترونيًا من {{ $brand['siteName'] }}</div>
@endsection
