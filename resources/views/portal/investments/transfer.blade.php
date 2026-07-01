@extends('layouts.portal')

@section('title', 'إتمام التحويل')

@section('content')
    <x-ip.page-header title="إتمام التحويل" :subtitle="$investment->contract?->title">
        <x-slot:actions>
            <a href="{{ route('contracts.show', $investment->contract) }}" style="color: var(--ip-muted); font-size:13px;">→ العقد</a>
        </x-slot:actions>
    </x-ip.page-header>

    {{-- Amount to transfer --}}
    <x-ip.hero-balance
        title="المبلغ المطلوب تحويله"
        :value="money($investment->amount)"
        :description="$investment->shares.' حصة'" />

    {{-- Steps --}}
    <x-ip.section-header title="١) حوّل المبلغ إلى أحد الحسابين" />
    @forelse ($banks as $bank)
        <x-ip.card>
            <div class="ip-kv">
                <div class="ip-kv__row"><span class="ip-kv__label">البنك</span><span class="ip-kv__value">{{ $bank['name'] }}</span></div>
                @if ($bank['account_name'])
                    <div class="ip-kv__row"><span class="ip-kv__label">اسم الحساب</span><span class="ip-kv__value">{{ $bank['account_name'] }}</span></div>
                @endif
                @if ($bank['iban'])
                    <div class="ip-kv__row" style="align-items:center;">
                        <span class="ip-kv__label">الآيبان (IBAN)</span>
                        <span class="ip-kv__value" style="display:flex; align-items:center; gap:8px;">
                            <span dir="ltr" style="font-family:monospace;">{{ $bank['iban'] }}</span>
                            <button type="button" class="ip-iconbtn" title="نسخ" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $bank['iban'] }}')"><i class="ti ti-copy"></i></button>
                        </span>
                    </div>
                @endif
            </div>
        </x-ip.card>
    @empty
        <x-ip.card>
            <p class="ip-note" style="margin:0;">لم تُضبط الحسابات البنكية بعد. يرجى التواصل مع الدعم لإتمام التحويل.</p>
        </x-ip.card>
    @endforelse

    {{-- Upload receipt --}}
    <x-ip.section-header title="٢) ارفع إيصال التحويل" />
    <x-ip.card>
        @if ($errors->any())
            <div class="ip-banner ip-banner--warning" style="margin:0 0 12px;">
                <span class="ip-banner__icon"><i class="ti ti-alert-triangle"></i></span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        <form method="POST" action="{{ route('portal.investments.receipt', $investment) }}" enctype="multipart/form-data">
            @csrf
            <div class="ip-form-group">
                <label class="ip-label" for="receipt">إيصال التحويل (PDF أو صورة، حتى 5 ميجابايت)</label>
                <input type="file" id="receipt" name="receipt" class="ip-input" accept=".pdf,image/*" required>
            </div>
            <button type="submit" class="ip-btn ip-btn--block" style="margin-top:8px;">إرسال الإيصال وإتمام الاشتراك</button>
        </form>
        <p class="ip-note" style="margin-top:12px;">بعد الإرسال سيراجع الفريق التحويل ويعتمد مشاركتك، ثم يظهر جدول التوزيعات في لوحتك.</p>
    </x-ip.card>
@endsection
