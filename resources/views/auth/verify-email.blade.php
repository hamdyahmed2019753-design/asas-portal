@extends('layouts.auth-portal')

@section('title', 'توثيق البريد الإلكتروني')

@section('content')
    <x-ip.auth-card
        title="وثّق بريدك الإلكتروني"
        subtitle="أرسلنا رابط تفعيل إلى بريدك. يرجى الضغط على الرابط لتأكيد حسابك قبل المتابعة.">

        <div class="ip-banner ip-banner--warning" style="margin-bottom:16px;">
            <span class="ip-banner__icon"><i class="ti ti-mail-exclamation"></i></span>
            <span>لم يتم توثيق بريدك الإلكتروني بعد — لن تتمكن من دخول البوابة قبل التوثيق.</span>
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

        <x-slot:footer>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ip-link" style="background:none; border:0; cursor:pointer;">تسجيل الخروج</button>
            </form>
        </x-slot:footer>
    </x-ip.auth-card>
@endsection
