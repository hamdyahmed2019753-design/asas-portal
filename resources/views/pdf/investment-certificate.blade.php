@extends('pdf.layout')

@section('title', 'شهادة استثمار')

@section('content')
    <div class="center" style="margin-top:20px;">
        <p>تشهد منصة {{ $brand['siteName'] }} بأن</p>
        <p class="big">{{ $investment->user->name }}</p>
        <p>مستثمر مشارك في العقد الاستثماري</p>
        <p style="font-size:15px; font-weight:bold;">«{{ $investment->contract?->title }}»</p>

        <table class="kv" style="width:70%; margin:18px auto;">
            <tr><td class="k">قيمة المشاركة</td><td class="v">{{ money($investment->amount) }}</td></tr>
            <tr><td class="k">العائد المتوقع</td><td class="v">{{ $return }}</td></tr>
            <tr><td class="k">تاريخ المشاركة</td><td class="v">{{ ($investment->start_date ?? $investment->created_at)?->translatedFormat('d F Y') ?? '—' }}</td></tr>
        </table>

        <div class="seal" style="width:60%; margin:18px auto 0;">
            شهادة رقم #{{ $investment->id }}<br>
            <span class="muted">معتمدة إلكترونيًا — {{ $brand['issuedAt'] }}</span>
        </div>
    </div>
@endsection
