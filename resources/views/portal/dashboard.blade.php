@extends('layouts.portal')

@section('title', 'لوحتي')

@section('content')
    <x-ip.page-header title="لوحتي" subtitle="نظرة سريعة على محفظتك الاستثمارية في أساس." />

    @if ($nextStep)
        <x-ip.next-step :step="$nextStep" />
    @endif

    @if ($kyc)
        <x-ip.kyc-progress :kyc="$kyc" />
    @endif

    @if ($pendingInterests > 0)
        <div class="ip-banner ip-banner--info" style="justify-content:space-between;">
            <span style="display:flex; align-items:center; gap:10px;">
                <span class="ip-banner__icon"><i class="ti ti-bell-ringing"></i></span>
                <span>لديك {{ $pendingInterests }} طلب اهتمام قيد المراجعة.</span>
            </span>
            <a href="{{ route('contracts.index') }}" class="ip-btn">استعراض العقود</a>
        </div>
    @endif

    {{-- Documents quick access --}}
    <div class="ip-banner ip-banner--info" style="justify-content:space-between;">
        <span style="display:flex; align-items:center; gap:10px;">
            <span class="ip-banner__icon"><i class="ti ti-files"></i></span>
            <span>
                لديك {{ $documents['total'] }} مستند في مركز المستندات.
                @if ($documents['lastTitle']) آخر مستند: {{ $documents['lastTitle'] }} ({{ $documents['lastDate'] }}). @endif
            </span>
        </span>
        <a href="{{ route('portal.documents') }}" class="ip-btn">مركز المستندات</a>
    </div>

    {{-- Security score --}}
    <div class="ip-banner ip-banner--{{ $security['color'] }}" style="justify-content:space-between;">
        <span style="display:flex; align-items:center; gap:10px;">
            <span class="ip-banner__icon"><i class="ti ti-shield-lock"></i></span>
            <span>أمان الحساب: {{ $security['score'] }}/100 — {{ $security['status'] }}.</span>
        </span>
        <a href="{{ route('portal.settings') }}" class="ip-btn">إعدادات الأمان</a>
    </div>

    @unless ($hasInvestments)
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-wallet"
                title="لا توجد مشاركات بعد"
                description="ابدأ باستعراض العقود المتاحة وإبداء اهتمامك بالفرص الاستثمارية.">
                <x-slot:action>
                    <a href="{{ route('contracts.index') }}" class="ip-btn">العقود الاستثمارية</a>
                </x-slot:action>
            </x-ip.empty-state>
        </x-ip.card>
    @else
        {{-- Hero --}}
        <x-ip.hero-balance
            title="إجمالي قيمة المحفظة"
            :value="money($portfolioValue)"
            description="رأس المال المستثمَر + الأرباح المحققة." />

        {{-- KPIs --}}
        <x-ip.section-header title="المؤشرات" />
        <div class="ip-grid">
            <x-ip.stat-card color="primary" icon="ti-wallet" label="إجمالي رأس المال المستثمَر" :value="money($totalInvested)" />
            <x-ip.stat-card color="success" icon="ti-trending-up" label="إجمالي الأرباح المحققة" :value="money($profitPaid)" />
            <x-ip.stat-card color="info" icon="ti-target" label="الأرباح المتوقعة" :value="money($profitExpected)" />
            <x-ip.stat-card color="warning" icon="ti-calendar" label="التوزيعة القادمة"
                :value="$nextPayout && $nextPayout->amount !== null ? money($nextPayout->amount) : '—'" />
            <x-ip.stat-card color="primary" icon="ti-folder" label="عدد المشاركات" :value="$investmentsCount" />
            <x-ip.stat-card color="success" icon="ti-circle-check" label="المشاركات النشطة" :value="$activeCount" />
        </div>

        {{-- Growth chart + upcoming payout --}}
        <div class="ip-dash-split" style="display:grid; grid-template-columns:1.5fr 1fr; gap:14px; margin-top:14px;">
            <x-ip.chart-card title="تطور الأرباح" subtitle="آخر 12 شهرًا">
                <div style="width:100%; height:240px;"><canvas id="growthChart" role="img" aria-label="تطور الأرباح المحققة خلال آخر 12 شهرًا"></canvas></div>
            </x-ip.chart-card>

            @if ($nextPayout)
                <div style="background:var(--ip-highlight); color:var(--ip-on-accent); border-radius:var(--ip-radius); padding:20px; box-shadow:var(--ip-shadow-lg);">
                    <div style="font-size:13px; opacity:.85;">التوزيعة القادمة</div>
                    <div style="font-size:26px; font-weight:700; margin-top:6px;">{{ $nextPayout->amount !== null ? money($nextPayout->amount) : 'غير محددة' }}</div>
                    <div style="font-size:13px; opacity:.9; margin-top:12px; display:flex; align-items:center; gap:6px;"><i class="ti ti-calendar"></i> {{ $nextPayout->due_date?->format('Y-m-d') }}</div>
                    <div style="font-size:13px; opacity:.9; margin-top:4px; display:flex; align-items:center; gap:6px;"><i class="ti ti-file-text"></i> {{ $nextPayout->investment?->contract?->title }}</div>
                </div>
            @else
                <x-ip.card title="التوزيعة القادمة">
                    <x-ip.empty-state icon="ti-calendar-off" title="لا توجد توزيعات قادمة" description="ستظهر هنا توزيعتك القادمة فور جدولتها." />
                </x-ip.card>
            @endif
        </div>

        {{-- Latest investments --}}
        <x-ip.section-header title="أحدث المشاركات">
            <x-slot:action><a href="{{ route('portal.investments') }}" style="color: var(--ip-primary-600);">عرض الكل</a></x-slot:action>
        </x-ip.section-header>

        <x-ip.data-table>
            <x-slot:header>
                <tr><th>العقد</th><th>المبلغ</th><th>الحالة</th><th>تاريخ البداية</th><th>تاريخ النهاية</th></tr>
            </x-slot:header>
            @foreach ($latestInvestments as $investment)
                <tr>
                    <td>{{ $investment->contract?->title }}</td>
                    <td>{{ money($investment->amount) }}</td>
                    <td><x-ip.status-pill :color="$investment->status_color" :label="$investment->status_label" /></td>
                    <td>{{ $investment->start_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $investment->end_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @endforeach
        </x-ip.data-table>

        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
            <script>
                (function () {
                    var el = document.getElementById('growthChart');
                    if (!el || !window.Chart) return;
                    new Chart(el, {
                        type: 'line',
                        data: {
                            labels: @json($growth['labels']),
                            datasets: [{
                                data: @json($growth['data']),
                                borderColor: '#4338CA',
                                backgroundColor: 'rgba(67,56,202,0.12)',
                                fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 0,
                            }],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { family: 'IBM Plex Sans Arabic' } } },
                                y: { grid: { color: 'rgba(120,120,140,0.12)' }, ticks: { font: { family: 'IBM Plex Sans Arabic' } } },
                            },
                        },
                    });
                })();
            </script>
        @endpush
    @endunless
@endsection
