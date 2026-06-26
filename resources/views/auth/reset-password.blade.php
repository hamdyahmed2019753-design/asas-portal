@extends('layouts.auth-portal')

@section('title', 'إعادة تعيين كلمة المرور')

@section('content')
    <x-ip.auth-card
        title="إعادة تعيين كلمة المرور"
        subtitle="اختر كلمة مرور جديدة لحسابك للمتابعة بأمان.">

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="ip-form-group">
                <label class="ip-label" for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus
                       autocomplete="username" placeholder="you@example.com"
                       class="ip-input @error('email') ip-input--error @enderror">
                @error('email')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="password">كلمة المرور الجديدة</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       placeholder="••••••••" class="ip-input @error('password') ip-input--error @enderror">
                @error('password')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ip-form-group">
                <label class="ip-label" for="password_confirmation">تأكيد كلمة المرور</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" placeholder="••••••••" class="ip-input">
            </div>

            <button type="submit" class="ip-btn ip-btn--block" style="margin-top:6px;">إعادة تعيين كلمة المرور</button>
        </form>
    </x-ip.auth-card>
@endsection
