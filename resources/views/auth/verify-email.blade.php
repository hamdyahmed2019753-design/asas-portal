@extends('layouts.auth-portal')

@section('title', 'توثيق البريد الإلكتروني')

@section('content')
    <x-ip.auth-card
        title="وثّق بريدك الإلكتروني"
        subtitle="أرسلنا رابط تفعيل إلى بريدك. يرجى الضغط على الرابط لتأكيد حسابك قبل المتابعة.">

        <div class="ip-banner ip-banner--warning" style="margin-bottom:16px;">
            <span class="ip-banner__icon"><i class="ti ti-mail-exclamation"></i></span>
            <span>لم يتم توثيق بريدك الإلكتروني بعد — التوثيق مطلوب فقط للمشاركة في العقود.</span>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="ip-banner ip-banner--success" style="margin-bottom:16px;">
                <span class="ip-banner__icon"><i class="ti ti-circle-check"></i></span>
                <span>تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="ip-btn ip-btn--block">إعادة إرسال رسالة التحقق</button>
        </form>

        {{-- Explore-before-verify: the wall is never a dead-end — browse contracts now. --}}
        <a href="{{ route('contracts.index') }}" class="ip-btn ip-btn--block"
           style="margin-top:10px; background:transparent; color:var(--ip-primary); border:1px solid var(--ip-border);">
            <i class="ti ti-search"></i> تصفّح العقود المتاحة الآن
        </a>

        <x-slot:footer>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ip-link" style="background:none; border:0; cursor:pointer;">تسجيل الخروج</button>
            </form>
        </x-slot:footer>
    </x-ip.auth-card>
@endsection
