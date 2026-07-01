@extends('layouts.portal')

@section('title', 'الإعدادات والأمان')

@section('content')
    <x-ip.page-header title="الإعدادات والأمان" subtitle="إدارة بياناتك وكلمة مرورك وجلساتك النشطة." />

    @if (session('status'))
        <div class="ip-banner ip-banner--success">
            <span class="ip-banner__icon"><i class="ti ti-circle-check"></i></span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Security score --}}
    <x-ip.card>
        <div class="ip-kyc__head" style="font-size:15px; color:var(--ip-text); font-weight:600;">
            <span>درجة أمان الحساب</span>
            <x-ip.status-pill :color="$security['color']" :label="$security['score'].'/100 · '.$security['status']" />
        </div>
        <div class="ip-wizard__bar" style="margin:4px 0 14px;"><span class="ip-wizard__fill" style="width: {{ $security['score'] }}%;"></span></div>
        <div class="ip-sec-factors">
            @foreach ($security['factors'] as $factor)
                <div class="ip-sec-factor">
                    <i class="ti {{ $factor['done'] ? 'ti-circle-check' : 'ti-circle' }} ip-sec-factor__icon--{{ $factor['done'] ? 'on' : 'off' }}"></i>
                    <span>{{ $factor['label'] }}</span>
                </div>
            @endforeach
        </div>
    </x-ip.card>

    {{-- Profile settings --}}
    <x-ip.section-header title="البيانات الأساسية" />
    <x-ip.card>
        <form method="POST" action="{{ route('portal.settings.profile') }}">
            @csrf
            @method('PATCH')
            <div class="ip-grid ip-grid--2">
                <div class="ip-form-group">
                    <label class="ip-label" for="name">الاسم</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                           class="ip-input @error('name') ip-input--error @enderror">
                    @error('name')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="email">البريد الإلكتروني</label>
                    <input id="email" type="email" value="{{ $user->email }}" disabled class="ip-input" style="opacity:.7;">
                    <div class="ip-note">لا يمكن تغيير البريد الإلكتروني مباشرة.</div>
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="phone">رقم الهاتف</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}"
                           class="ip-input @error('phone') ip-input--error @enderror" placeholder="05XXXXXXXX">
                    @error('phone')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="city">المدينة</label>
                    <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}"
                           class="ip-input @error('city') ip-input--error @enderror">
                    @error('city')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="country">الدولة</label>
                    <input id="country" name="country" type="text" value="{{ old('country', $user->country) }}"
                           class="ip-input @error('country') ip-input--error @enderror">
                    @error('country')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="ip-btn" style="margin-top:6px;">حفظ التغييرات</button>
        </form>
    </x-ip.card>

    {{-- Bank account (payout destination) --}}
    <x-ip.section-header title="الحساب البنكي" />
    <x-ip.card>
        <p class="ip-note" style="margin:0 0 12px;">يُستخدم هذا الحساب لاستلام أرباحك والمبالغ المسحوبة من محفظتك.</p>
        <form method="POST" action="{{ route('portal.settings.bank') }}">
            @csrf
            @method('PATCH')
            <div class="ip-grid ip-grid--2">
                <div class="ip-form-group">
                    <label class="ip-label" for="bank_name">اسم البنك</label>
                    <input id="bank_name" name="bank_name" type="text" value="{{ old('bank_name', $user->bank_name) }}" required
                           class="ip-input @error('bank_name') ip-input--error @enderror">
                    @error('bank_name')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="bank_account_name">اسم صاحب الحساب</label>
                    <input id="bank_account_name" name="bank_account_name" type="text" value="{{ old('bank_account_name', $user->bank_account_name) }}" required
                           class="ip-input @error('bank_account_name') ip-input--error @enderror">
                    @error('bank_account_name')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group" style="grid-column:1 / -1;">
                    <label class="ip-label" for="bank_iban">الآيبان (IBAN)</label>
                    <input id="bank_iban" name="bank_iban" type="text" dir="ltr" value="{{ old('bank_iban', $user->bank_iban) }}" required
                           placeholder="SA0000000000000000000000"
                           class="ip-input @error('bank_iban') ip-input--error @enderror">
                    @error('bank_iban')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="ip-btn" style="margin-top:6px;">حفظ الحساب البنكي</button>
        </form>
    </x-ip.card>

    {{-- Password --}}
    <x-ip.section-header title="تغيير كلمة المرور" />
    <x-ip.card>
        <form method="POST" action="{{ route('portal.settings.password') }}">
            @csrf
            @method('PUT')
            <div class="ip-form-group">
                <label class="ip-label" for="current_password">كلمة المرور الحالية</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                       class="ip-input @error('current_password') ip-input--error @enderror">
                @error('current_password')<div class="ip-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="ip-grid ip-grid--2">
                <div class="ip-form-group">
                    <label class="ip-label" for="password">كلمة المرور الجديدة</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                           class="ip-input @error('password') ip-input--error @enderror">
                    @error('password')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="password_confirmation">تأكيد كلمة المرور</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="ip-input">
                </div>
            </div>
            <button type="submit" class="ip-btn" style="margin-top:6px;">تحديث كلمة المرور</button>
        </form>
    </x-ip.card>

    {{-- Two-step verification (coming soon) --}}
    <x-ip.section-header title="التحقق بخطوتين (2FA)" />
    <x-ip.card>
        <div class="ip-kv__row" style="border-bottom:0; padding:0;">
            <span class="ip-kv__label">طبقة حماية إضافية عند تسجيل الدخول</span>
            <x-ip.status-pill color="gray" label="قريبًا" />
        </div>
    </x-ip.card>

    {{-- Active sessions --}}
    <x-ip.section-header title="الجلسات النشطة">
        @if ($sessions->count() > 1)
            <x-slot:action>
                <form method="POST" action="{{ route('portal.settings.logoutOthers') }}" x-data="{ open: false }">
                    @csrf
                    <button type="button" class="ip-link" @click="open = true">تسجيل الخروج من الأجهزة الأخرى</button>
                    <template x-if="open">
                        <div>
                            <div class="ip-drawer-backdrop" @click="open = false"></div>
                            <div class="ip-modal">
                                <div class="ip-modal__head"><span class="ip-card__title" style="font-size:16px;">تأكيد كلمة المرور</span>
                                    <button type="button" class="ip-iconbtn" @click="open = false"><i class="ti ti-x"></i></button></div>
                                <div class="ip-form-group">
                                    <label class="ip-label" for="password_confirm_logout">كلمة المرور</label>
                                    <input id="password_confirm_logout" name="password" type="password" class="ip-input @error('password') ip-input--error @enderror">
                                    @error('password')<div class="ip-field-error">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="ip-btn ip-btn--block">تسجيل الخروج من الأجهزة الأخرى</button>
                            </div>
                        </div>
                    </template>
                </form>
            </x-slot:action>
        @endif
    </x-ip.section-header>
    <x-ip.data-table>
        <x-slot:header>
            <tr><th>الجهاز</th><th>المتصفح</th><th>عنوان IP</th><th>آخر نشاط</th><th></th></tr>
        </x-slot:header>
        @foreach ($sessions as $session)
            <tr>
                <td>{{ $session['device'] }}</td>
                <td>{{ $session['browser'] }}</td>
                <td>{{ $session['ip'] }}</td>
                <td>{{ $session['lastActivity']->diffForHumans() }}</td>
                <td>@if ($session['current'])<x-ip.status-pill color="success" label="الجلسة الحالية" />@endif</td>
            </tr>
        @endforeach
    </x-ip.data-table>

    {{-- Login history --}}
    <x-ip.section-header title="سجل الدخول" subtitle="آخر 20 عملية تسجيل دخول." />
    @if ($loginHistory->isEmpty())
        <x-ip.card>
            <x-ip.empty-state icon="ti-history-off" title="لا يوجد سجل دخول" description="ستظهر هنا عمليات تسجيل الدخول إلى حسابك." />
        </x-ip.card>
    @else
        <x-ip.data-table>
            <x-slot:header>
                <tr><th>التاريخ والوقت</th><th>عنوان IP</th><th>الجهاز/المتصفح</th></tr>
            </x-slot:header>
            @foreach ($loginHistory as $login)
                <tr>
                    <td>{{ $login->logged_in_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $login->ip_address ?? '—' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($login->user_agent, 40) ?? '—' }}</td>
                </tr>
            @endforeach
        </x-ip.data-table>
    @endif
@endsection
