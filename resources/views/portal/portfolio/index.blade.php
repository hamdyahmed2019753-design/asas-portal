@extends('layouts.portal')

@section('title', 'محفظتي')

@section('content')
    <x-ip.page-header title="محفظتي" subtitle="توزيع رأس مالك وأداء أرباحك عبر مشاركاتك." />

    @unless ($hasInvestments)
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-chart-pie"
                title="لا توجد مشاركات في محفظتك"
                description="ابدأ باستعراض العقود المتاحة وإبداء اهتمامك لبناء محفظتك الاستثمارية.">
                <x-slot:action>
                    <a href="{{ route('contracts.index') }}" class="ip-btn">العقود الاستثمارية</a>
                </x-slot:action>
            </x-ip.empty-state>
        </x-ip.card>
    @else
        {{-- KPIs --}}
        <div class="ip-grid">
            <x-ip.stat-card color="primary" icon="ti-wallet" label="إجمالي رأس المال" :value="money($kpis['totalCapital'])" />
            <x-ip.stat-card color="success" icon="ti-trending-up" label="الأرباح المحققة" :value="money($kpis['realizedProfit'])" />
            <x-ip.stat-card color="info" icon="ti-target" label="الأرباح المنتظرة" :value="money($kpis['expectedProfit'])" />
            <x-ip.stat-card color="warning" icon="ti-percentage" label="العائد السنوي" :value="rtrim(rtrim(number_format($kpis['averageReturn'], 2), '0'), '.').'%'" />
            <x-ip.stat-card color="primary" icon="ti-folder" label="العقود النشطة" :value="$kpis['activeCount']" />
            <x-ip.stat-card color="info" icon="ti-folder-check" label="العقود المنتهية" :value="$kpis['completedCount']" />
            <x-ip.stat-card color="success" icon="ti-coins" label="قيمة المحفظة" :value="money($kpis['portfolioValue'])" />
        </div>

        {{-- Charts --}}
        <div class="ip-dash-split" style="display:grid; grid-template-columns:1fr 1.5fr; gap:14px; margin-top:14px;">
            <x-ip.chart-card title="توزيع رأس المال" subtitle="حسب العقد">
                <div style="width:100%; height:260px;"><canvas id="allocationChart" role="img" aria-label="توزيع رأس المال حسب العقد"></canvas></div>
            </x-ip.chart-card>

            <x-ip.chart-card title="أداء الأرباح" subtitle="الأرباح المحققة شهريًا">
                <div style="width:100%; height:260px;"><canvas id="performanceChart" role="img" aria-label="الأرباح المحققة شهريًا"></canvas></div>
            </x-ip.chart-card>
        </div>

        {{-- Asset Allocation (where is my money?) --}}
        <x-ip.section-header title="توزيع الأصول" />
        <x-ip.card>
            @forelse ($assetAllocation as $row)
                <div style="{{ $loop->first ? '' : 'margin-top:14px;' }}">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; margin-bottom:6px;">
                        <span style="color:var(--ip-text); font-weight:600;">{{ $row['label'] }}</span>
                        <span style="color:var(--ip-text); opacity:.7;">{{ money($row['amount']) }} · {{ rtrim(rtrim(number_format($row['percentage'], 1), '0'), '.') }}%</span>
                    </div>
                    <div style="height:9px; background:var(--ip-primary-50); border-radius:99px; overflow:hidden;">
                        <div style="height:100%; width:{{ $row['percentage'] }}%; background:var(--ip-primary); border-radius:99px;"></div>
                    </div>
                </div>
            @empty
                <p class="ip-note" style="margin:0;">لا توجد بيانات توزيع.</p>
            @endforelse
        </x-ip.card>

        {{-- Upcoming Cashflow (when will I get paid?) --}}
        <x-ip.section-header title="التدفقات النقدية القادمة" />
        @if (count($upcoming['items']))
            <div class="ip-banner ip-banner--info" style="justify-content:space-between;">
                <span style="display:flex; align-items:center; gap:10px;">
                    <span class="ip-banner__icon"><i class="ti ti-calendar-dollar"></i></span>
                    <span>أقرب دفعة: {{ $upcoming['nextDate']?->translatedFormat('d M Y') }}</span>
                </span>
                @if ($upcoming['total'] > 0)
                    <strong>{{ money($upcoming['total']) }} مؤكدة</strong>
                @endif
            </div>
            <x-ip.data-table>
                <x-slot:header>
                    <tr><th>التاريخ</th><th>العقد</th><th>النوع</th><th>المبلغ</th><th>الحالة</th></tr>
                </x-slot:header>
                @foreach ($upcoming['items'] as $p)
                    <tr>
                        <td>{{ $p->due_date?->translatedFormat('d M Y') }}</td>
                        <td>{{ $p->investment?->contract?->title ?? '—' }}</td>
                        <td>{{ $p->type_label }}</td>
                        <td>{{ $p->amount !== null ? money($p->amount) : 'يُحدَّد لاحقًا' }}</td>
                        <td><x-ip.status-pill :color="$p->status_color" :label="$p->status_label" /></td>
                    </tr>
                @endforeach
            </x-ip.data-table>
        @else
            <x-ip.card>
                <p class="ip-note" style="margin:0;">لا توجد دفعات قادمة مجدولة حاليًا.</p>
            </x-ip.card>
        @endif

        {{-- Investments summary --}}
        <x-ip.section-header title="ملخص المشاركات">
            <x-slot:action><a href="{{ route('portal.investments') }}" style="color: var(--ip-primary-600);">عرض الكل</a></x-slot:action>
        </x-ip.section-header>

        <x-ip.data-table>
            <x-slot:header>
                <tr><th>العقد</th><th>رأس المال</th><th>الحالة</th><th>العائد المتوقع</th><th>الأرباح المدفوعة</th></tr>
            </x-slot:header>
            @foreach ($investments as $investment)
                <tr>
                    <td>{{ $investment->contract?->title }}</td>
                    <td>{{ money($investment->amount) }}</td>
                    <td><x-ip.status-pill :color="$investment->status_color" :label="$investment->status_label" /></td>
                    <td>{{ $investment->contract?->expected_return !== null ? rtrim(rtrim((string) $investment->contract->expected_return, '0'), '.').'%' : '—' }}</td>
                    <td>{{ money($investment->paid_profit ?? 0) }}</td>
                </tr>
            @endforeach
        </x-ip.data-table>

        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
            <script>
                (function () {
                    if (!window.Chart) return;
                    var font = 'IBM Plex Sans Arabic';
                    var palette = ['#4338CA', '#6366F1', '#0F766E', '#059669', '#A15C07', '#3730A3', '#9333EA'];

                    var alloc = document.getElementById('allocationChart');
                    if (alloc) {
                        new Chart(alloc, {
                            type: 'doughnut',
                            data: {
                                labels: @json($allocation['labels']),
                                datasets: [{
                                    data: @json($allocation['data']),
                                    backgroundColor: palette,
                                    borderWidth: 0,
                                }],
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '62%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { font: { family: font }, padding: 14, usePointStyle: true } },
                                    tooltip: { callbacks: { label: function (c) {
                                        var pct = @json($allocation['percentages']);
                                        return ' ' + c.label + ': ' + pct[c.dataIndex] + '%';
                                    } } },
                                },
                            },
                        });
                    }

                    var perf = document.getElementById('performanceChart');
                    if (perf) {
                        new Chart(perf, {
                            type: 'bar',
                            data: {
                                labels: @json(collect($performance['labels'])->map(fn ($ym) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M Y'))->all()),
                                datasets: [{
                                    data: @json($performance['data']),
                                    backgroundColor: 'rgba(67,56,202,0.85)',
                                    borderRadius: 6, maxBarThickness: 34,
                                }],
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { family: font } } },
                                    y: { grid: { color: 'rgba(120,120,140,0.12)' }, ticks: { font: { family: font } } },
                                },
                            },
                        });
                    }
                })();
            </script>
        @endpush
    @endunless
@endsection
