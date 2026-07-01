@extends('layouts.portal')

@section('title', 'سحب من الرصيد')

@section('content')
    <x-ip.page-header title="سحب من الرصيد" subtitle="اطلب تحويل رصيدك إلى حسابك البنكي المسجّل.">
        <x-slot:actions>
            <a href="{{ route('portal.wallet') }}" style="color: var(--ip-muted); font-size:13px;">→ رصيدي</a>
        </x-slot:actions>
    </x-ip.page-header>

    <x-ip.hero-balance title="الرصيد المتاح للسحب" :value="money($balance)" />

    {{-- Request form --}}
    <x-ip.section-header title="طلب سحب" />
    <x-ip.card>
        @if ($errors->any())
            <div class="ip-banner ip-banner--warning" style="margin:0 0 12px;">
                <span class="ip-banner__icon"><i class="ti ti-alert-triangle"></i></span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div class="ip-kv" style="margin-bottom:14px;">
            <div class="ip-kv__row"><span class="ip-kv__label">يُحوَّل إلى</span><span class="ip-kv__value">{{ $user->bank_name }} — {{ $user->bank_account_name }}</span></div>
            <div class="ip-kv__row"><span class="ip-kv__label">الآيبان</span><span class="ip-kv__value" dir="ltr" style="font-family:monospace;">{{ $user->bank_iban }}</span></div>
        </div>

        <form method="POST" action="{{ route('portal.wallet.withdraw.store') }}">
            @csrf
            <div class="ip-form-group">
                <label class="ip-label" for="amount">المبلغ (ر.س)</label>
                <input type="number" id="amount" name="amount" class="ip-input" min="1" max="{{ $balance }}" step="0.01"
                       value="{{ old('amount') }}" required>
            </div>
            <button type="submit" class="ip-btn ip-btn--block" @disabled($balance <= 0)>إرسال طلب السحب</button>
        </form>
    </x-ip.card>

    {{-- History --}}
    <x-ip.section-header title="طلبات السحب" />
    @if ($withdrawals->isNotEmpty())
        <x-ip.data-table>
            <x-slot:header>
                <tr><th>التاريخ</th><th>المبلغ</th><th>الحالة</th><th>الإيصال</th></tr>
            </x-slot:header>
            @foreach ($withdrawals as $w)
                <tr>
                    <td>{{ $w->created_at?->format('Y-m-d') }}</td>
                    <td>{{ money($w->amount) }}</td>
                    <td><x-ip.status-pill :color="$w->status_color" :label="$w->status_label" /></td>
                    <td>
                        @if ($w->status->value === 'paid' && $w->receipt_path)
                            <a href="{{ route('portal.withdrawals.receipt', $w) }}" target="_blank" class="ip-link" title="تحميل الإيصال"><i class="ti ti-download"></i></a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ip.data-table>
    @else
        <x-ip.card>
            <p class="ip-note" style="margin:0;">لا توجد طلبات سحب سابقة.</p>
        </x-ip.card>
    @endif
@endsection
