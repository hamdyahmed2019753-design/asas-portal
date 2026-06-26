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
            <x-ip.stat-card color="info" icon="ti-target" label="الأرباح المتوقعة" :value="money($kpis['expectedProfit'])" />
            <x-ip.stat-card color="primary" icon="ti-folder" label="المشاركات النشطة" :value="$kpis['activeCount']" />
            <x-ip.stat-card color="warning" icon="ti-percentage" label="متوسط العائد" :value="rtrim(rtrim(number_format($kpis['averageReturn'], 2), '0'), '.').'%'" />
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
                                labels: @json($performance['labels']),
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
