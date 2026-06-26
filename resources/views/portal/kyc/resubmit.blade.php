@extends('layouts.portal')

@section('title', 'إعادة رفع المستندات')

@section('content')
    <x-ip.page-header title="إعادة رفع المستندات" subtitle="أعد رفع مستنداتك بعد رفض الطلب السابق لإعادة المراجعة." />

    @if ($errors->any())
        <div class="ip-banner ip-banner--danger" style="margin-bottom:6px;">
            <span class="ip-banner__icon"><i class="ti ti-alert-triangle"></i></span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <x-ip.card>
        <x-ip.section-header title="المستندات" subtitle="صيغ مقبولة: PDF, JPG, PNG — بحد أقصى 5 ميجابايت." />
        <form method="POST" action="{{ route('portal.kyc.resubmit.store') }}" enctype="multipart/form-data">
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
            <button type="submit" class="ip-btn ip-btn--block" style="margin-top:8px;">إعادة الإرسال للمراجعة</button>
        </form>
    </x-ip.card>
@endsection
