@extends('layouts.portal')

@section('title', 'مشاركاتي')

@section('content')
    <x-ip.page-header title="مشاركاتي" subtitle="جميع مشاركاتك الاستثمارية في أساس." />

    <form method="GET" class="ip-filters">
        <div class="ip-field">
            <span class="ip-field__label">الحالة</span>
            <select name="status" class="ip-select" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ip-field">
            <span class="ip-field__label">العقد</span>
            <select name="contract" class="ip-select" onchange="this.form.submit()">
                <option value="">كل العقود</option>
                @foreach ($contracts as $contract)
                    <option value="{{ $contract->id }}" @selected((string) request('contract') === (string) $contract->id)>{{ $contract->title }}</option>
                @endforeach
            </select>
        </div>
        <label class="ip-field" style="flex-direction:row; align-items:center; gap:6px;">
            <input type="checkbox" name="active" value="1" onchange="this.form.submit()" @checked(request()->boolean('active'))>
            <span class="ip-field__label">النشطة فقط</span>
        </label>
        <button type="submit" class="ip-btn">تصفية</button>
    </form>

    @if ($investments->isEmpty())
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-folder-search"
                title="لا توجد مشاركات"
                description="لم تقدّم أي مشاركة مطابقة بعد — استعرض العقود المتاحة للبدء.">
                <x-slot:action>
                    <a href="{{ route('contracts.index') }}" class="ip-btn">العقود الاستثمارية</a>
                </x-slot:action>
            </x-ip.empty-state>
        </x-ip.card>
    @else
        <x-ip.data-table>
            <x-slot:header>
                <tr>
                    <th>العقد</th><th>المبلغ</th><th>الحالة</th><th>تاريخ البداية</th>
                    <th>تاريخ النهاية</th><th>التوزيعات</th><th>التفاصيل</th>
                </tr>
            </x-slot:header>
            @foreach ($investments as $investment)
                <tr>
                    <td>{{ $investment->contract?->title }}</td>
                    <td>{{ money($investment->amount) }}</td>
                    <td><x-ip.status-pill :color="$investment->status_color" :label="$investment->status_label" /></td>
                    <td>{{ $investment->start_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $investment->end_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $investment->payouts_count }}</td>
                    <td><a href="{{ route('portal.investments.show', $investment) }}" style="color: var(--ip-primary-600);">عرض</a></td>
                </tr>
            @endforeach
        </x-ip.data-table>

        <div class="ip-pagination">{{ $investments->onEachSide(1)->links() }}</div>
    @endif
@endsection
