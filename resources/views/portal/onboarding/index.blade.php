@extends('layouts.portal')

@section('title', 'إكمال التسجيل')

@section('content')
    <x-ip.page-header title="إكمال التسجيل" subtitle="بقي القليل لتفعيل حسابك والبدء في الاستثمار." />

    <x-ip.wizard-steps
        :steps="['البيانات', 'المستندات', 'الشروط', 'تم']"
        :current="$step"
        :progress="$progress" />

    @if ($errors->any())
        <div class="ip-banner ip-banner--danger" style="margin-top:6px;">
            <span class="ip-banner__icon"><i class="ti ti-alert-triangle"></i></span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- Step 1 — Profile --}}
    @if ($step === 1)
        <x-ip.card>
            <x-ip.section-header title="استكمال البيانات" />
            <form method="POST" action="{{ route('portal.onboarding.profile') }}">
                @csrf
                <div class="ip-form-group">
                    <label class="ip-label" for="name">الاسم الكامل</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                           class="ip-input @error('name') ip-input--error @enderror" placeholder="الاسم الكامل">
                    @error('name')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="phone">رقم الهاتف</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required
                           class="ip-input @error('phone') ip-input--error @enderror" placeholder="05XXXXXXXX">
                    @error('phone')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-grid ip-grid--2">
                    <div class="ip-form-group">
                        <label class="ip-label" for="city">المدينة</label>
                        <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}" required
                               class="ip-input @error('city') ip-input--error @enderror" placeholder="المدينة">
                        @error('city')<div class="ip-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="ip-form-group">
                        <label class="ip-label" for="country">الدولة</label>
                        <input id="country" name="country" type="text" value="{{ old('country', $user->country) }}" required
                               class="ip-input @error('country') ip-input--error @enderror" placeholder="الدولة">
                        @error('country')<div class="ip-field-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button type="submit" class="ip-btn ip-btn--block" style="margin-top:8px;">التالي</button>
            </form>
        </x-ip.card>
    @endif

    {{-- Step 2 — Documents --}}
    @if ($step === 2)
        <x-ip.card>
            <x-ip.section-header title="رفع المستندات" subtitle="صيغ مقبولة: PDF, JPG, PNG — بحد أقصى 5 ميجابايت." />
            <form method="POST" action="{{ route('portal.onboarding.documents') }}" enctype="multipart/form-data">
                @csrf
                <div class="ip-form-group">
                    <label class="ip-label" for="identity">صورة الهوية</label>
                    <input id="identity" name="identity" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                           class="ip-input ip-file @error('identity') ip-input--error @enderror">
                    @error('identity')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="iban">شهادة الآيبان (IBAN)</label>
                    <input id="iban" name="iban" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                           class="ip-input ip-file @error('iban') ip-input--error @enderror">
                    @error('iban')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="ip-form-group">
                    <label class="ip-label" for="address">إثبات العنوان</label>
                    <input id="address" name="address" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                           class="ip-input ip-file @error('address') ip-input--error @enderror">
                    @error('address')<div class="ip-field-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="ip-btn ip-btn--block" style="margin-top:8px;">التالي</button>
            </form>
        </x-ip.card>
    @endif

    {{-- Step 3 — Terms --}}
    @if ($step === 3)
        <x-ip.card>
            <x-ip.section-header title="مراجعة الشروط" />
            <div class="ip-prose" style="max-height:240px; overflow:auto; border:1px solid var(--ip-border); border-radius:var(--ip-radius-sm); padding:16px; margin-bottom:16px;">
                <p>بمتابعتك إنشاء الحساب فإنك تقر بأن جميع البيانات والمستندات المقدمة صحيحة، وتوافق على سياسة الخصوصية وشروط الاستخدام الخاصة ببوابة مستثمري أساس.</p>
                <p>تخضع جميع الفرص الاستثمارية لمراجعة وموافقة الإدارة، ولا تُعد المعلومات المعروضة ضمانًا للعوائد المستقبلية.</p>
                <p>يحق لإدارة المنصة طلب مستندات إضافية للتحقق من الهوية (KYC) قبل اعتماد أي مشاركة استثمارية.</p>
            </div>
            <form method="POST" action="{{ route('portal.onboarding.terms') }}">
                @csrf
                <label class="ip-checkrow" style="margin-bottom:16px;">
                    <input type="checkbox" name="terms" value="1" class="ip-checkbox" required>
                    <span>أوافق على الشروط والأحكام وسياسة الخصوصية.</span>
                </label>
                @error('terms')<div class="ip-field-error" style="margin-bottom:12px;">{{ $message }}</div>@enderror
                <button type="submit" class="ip-btn ip-btn--block">إنهاء التسجيل</button>
            </form>
        </x-ip.card>
    @endif

    {{-- Step 4 — Success --}}
    @if ($step === 4)
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-circle-check"
                title="تم إكمال تسجيلك بنجاح"
                description="استلمنا بياناتك ومستنداتك، وحسابك الآن قيد المراجعة للتحقق. يمكنك البدء باستعراض الفرص الاستثمارية.">
                <x-slot:action>
                    <a href="{{ route('portal.dashboard') }}" class="ip-btn">الذهاب إلى لوحتي</a>
                </x-slot:action>
            </x-ip.empty-state>
        </x-ip.card>
    @endif
@endsection
