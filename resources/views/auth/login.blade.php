@extends('layouts.auth-portal')

@section('title', 'تسجيل الدخول')

@section('content')
    <x-ip.auth-card
        title="مرحبًا بعودتك"
        subtitle="سجّل الدخول للوصول إلى محفظتك الاستثمارية ومتابعة أرباحك وتوزيعاتك.">

        @if (session('status'))
            <div class="ip-auth-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="ip-form-group">
                <label class="ip-label" for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" placeholder="you@example.com"
                       class="ip-input @error('email') ip-input--error @enderror">
                @error('email')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       placeholder="••••••••" class="ip-input @error('password') ip-input--error @enderror">
                @error('password')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-auth-row">
                <label class="ip-checkrow">
                    <input type="checkbox" name="remember" class="ip-checkbox">
                    <span>تذكرني</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="ip-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                @endif
            </div>

            <button type="submit" class="ip-btn ip-btn--block">تسجيل الدخول</button>
        </form>

        <x-slot:footer>
            ليس لديك حساب؟ <a class="ip-link" href="{{ route('register') }}">إنشاء حساب</a>
        </x-slot:footer>
    </x-ip.auth-card>
@endsection
