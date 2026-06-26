@extends('layouts.portal')

@section('title', 'الملف الشخصي')

@section('content')
    <x-ip.page-header title="الملف الشخصي" subtitle="بياناتك وحالة التحقق وإحصائيات حسابك." />

    {{-- البيانات الأساسية --}}
    <x-ip.section-header title="البيانات الأساسية" />
    <x-ip.card>
        <div class="ip-kv">
            <div class="ip-kv__row"><span class="ip-kv__label">الاسم</span><span class="ip-kv__value">{{ $user->name }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">البريد الإلكتروني</span><span class="ip-kv__value">{{ $user->email }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">رقم الهاتف</span><span class="ip-kv__value">{{ $user->phone ?? '—' }}</span></div>
        </div>
    </x-ip.card>

    {{-- حالة التحقق (KYC) --}}
    <x-ip.section-header title="التحقق من الهوية (KYC)" />
    <x-ip.kyc-card :kyc="$kyc" />

    {{-- إحصائيات الحساب --}}
    <x-ip.section-header title="إحصائيات الحساب" />
    <div class="ip-grid">
        <x-ip.stat-card color="primary" icon="ti-briefcase" label="عدد المشاركات" :value="$stats['investments']" />
        <x-ip.stat-card color="info" icon="ti-wallet" label="إجمالي رأس المال" :value="money($stats['capital'])" />
        <x-ip.stat-card color="success" icon="ti-coins" label="إجمالي الأرباح" :value="money($stats['profit'])" />
        <x-ip.stat-card color="warning" icon="ti-calendar-dollar" label="عدد التوزيعات" :value="$stats['payouts']" />
    </div>
@endsection
