@extends('layouts.auth-portal')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <x-ip.auth-card
        title="نسيت كلمة المرور؟"
        subtitle="أدخل بريدك الإلكتروني وسنرسل لك رابطًا لإعادة تعيين كلمة المرور.">

        @if (session('status'))
            <div class="ip-auth-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="ip-form-group">
                <label class="ip-label" for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" placeholder="you@example.com"
                       class="ip-input @error('email') ip-input--error @enderror">
                @error('email')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="ip-btn ip-btn--block">إرسال رابط الاستعادة</button>
        </form>

        <x-slot:footer>
            تذكّرت كلمة المرور؟ <a class="ip-link" href="{{ route('login') }}">تسجيل الدخول</a>
        </x-slot:footer>
    </x-ip.auth-card>
@endsection
