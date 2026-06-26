@extends('layouts.portal')

@section('title', $title)

@section('content')
    <x-ip.page-header :title="$title" subtitle="هذه الصفحة قيد الإنشاء ضمن مراحل بناء البوابة." />

    <x-ip.card>
        <div class="ip-soon">
            <span class="ip-soon__badge">قريبًا</span>
            <div class="ip-soon__title">{{ $heading }}</div>
            <div class="ip-soon__desc">سيتم بناء هذه الصفحة في خطوة لاحقة من Phase 4.</div>
        </div>
    </x-ip.card>
@endsection
