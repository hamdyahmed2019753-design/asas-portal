@extends('layouts.auth-portal')

@section('title', 'إنشاء حساب')

@section('content')
    <x-ip.auth-card
        title="إنشاء حساب مستثمر"
        subtitle="أنشئ حسابك وابدأ استكشاف الفرص الاستثمارية المتاحة.">

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="ip-form-group">
                <label class="ip-label" for="name">الاسم</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       autocomplete="name" placeholder="الاسم الكامل"
                       class="ip-input @error('name') ip-input--error @enderror">
                @error('name')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       autocomplete="username" placeholder="you@example.com"
                       class="ip-input @error('email') ip-input--error @enderror">
                @error('email')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="phone">رقم الهاتف</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
                       autocomplete="tel" placeholder="05XXXXXXXX"
                       class="ip-input @error('phone') ip-input--error @enderror">
                @error('phone')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       placeholder="••••••••" class="ip-input @error('password') ip-input--error @enderror">
                @error('password')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="password_confirmation">تأكيد كلمة المرور</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" placeholder="••••••••" class="ip-input">
            </div>

            <button type="submit" class="ip-btn ip-btn--block" style="margin-top:6px;">إنشاء الحساب</button>
        </form>

        <x-slot:footer>
            لديك حساب بالفعل؟ <a class="ip-link" href="{{ route('login') }}">تسجيل الدخول</a>
        </x-slot:footer>
    </x-ip.auth-card>
@endsection
