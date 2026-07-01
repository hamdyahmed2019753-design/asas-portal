@extends('layouts.portal')

@section('title', 'رصيدي')

@section('content')
    <x-ip.page-header title="رصيدي" subtitle="رصيدك النقدي من رأس المال المسترد — اسحبه أو أعد استثماره." />

    @if (session('status'))
        <div class="ip-banner ip-banner--success">
            <span class="ip-banner__icon"><i class="ti ti-circle-check"></i></span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <x-ip.hero-balance title="الرصيد المتاح" :value="money($balance)" description="قابل للسحب أو إعادة الاستثمار." />

    <div style="display:flex; flex-wrap:wrap; gap:10px; margin:14px 0;">
        <a href="{{ route('contracts.index') }}" class="ip-btn"><i class="ti ti-box"></i> استثمر رصيدك في عقد</a>
        @if ($balance > 0 && Route::has('portal.wallet.withdraw'))
            <a href="{{ route('portal.wallet.withdraw') }}" class="ip-btn"
               style="background:transparent; color:var(--ip-primary); border:1px solid var(--ip-border);"><i class="ti ti-cash-banknote"></i> سحب إلى حسابي البنكي</a>
        @endif
    </div>

    @unless ($hasBankAccount)
        <div class="ip-banner ip-banner--warning" style="justify-content:space-between;">
            <span style="display:flex; align-items:center; gap:10px;">
                <span class="ip-banner__icon"><i class="ti ti-building-bank"></i></span>
                <span>أضِف حسابك البنكي حتى تتمكن من سحب رصيدك.</span>
            </span>
            <a href="{{ route('portal.settings') }}" class="ip-btn">إضافة الحساب البنكي</a>
        </div>
    @endunless

    <x-ip.section-header title="كشف الحركات" />
    @if ($transactions->isNotEmpty())
        <x-ip.data-table>
            <x-slot:header>
                <tr><th>التاريخ</th><th>الحركة</th><th>السبب</th><th>المبلغ</th></tr>
            </x-slot:header>
            @foreach ($transactions as $tx)
                <tr>
                    <td>{{ $tx->created_at?->format('Y-m-d') }}</td>
                    <td><x-ip.status-pill :color="$tx->direction->color()" :label="$tx->direction->label()" /></td>
                    <td>{{ $tx->reason->label() }}</td>
                    <td style="color: var(--ip-{{ $tx->direction->color() }}-700); font-weight:600;">
                        {{ $tx->direction->sign() > 0 ? '+' : '−' }} {{ money($tx->amount) }}
                    </td>
                </tr>
            @endforeach
        </x-ip.data-table>
    @else
        <x-ip.card>
            <x-ip.empty-state icon="ti-wallet" title="لا توجد حركات بعد"
                description="سيظهر رأس مالك هنا فور استرداده عند انتهاء عقودك." />
        </x-ip.card>
    @endif
@endsection
