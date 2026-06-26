@extends('layouts.portal')

@section('title', 'مستنداتي')

@section('content')
    <x-ip.page-header title="مستنداتي" subtitle="مركز مستنداتك: العقود وكشوف التوزيعات ومستندات التحقق في مكان واحد." />

    {{-- Summary cards --}}
    <div class="ip-grid ip-grid--3">
        <x-ip.stat-card color="primary" icon="ti-files" label="إجمالي المستندات" :value="$summary['total']" />
        <x-ip.stat-card color="info" icon="ti-file-upload" label="آخر مستند"
            :value="$summary['lastTitle'] ?? '—'" />
        <x-ip.stat-card color="success" icon="ti-calendar" label="تاريخ آخر مستند"
            :value="$summary['lastDate'] ?? '—'" />
    </div>

    {{-- Category chips (query-string) --}}
    @php
        $chipUrl = fn ($cat) => route('portal.documents', array_merge(request()->only(['q']), array_filter(['category' => $cat])));
    @endphp
    <div class="ip-tabs" style="margin:20px 0 14px; flex-wrap:wrap;">
        <a href="{{ $chipUrl(null) }}" @class(['ip-tabs__tab', 'is-active' => $filters['category'] === null])>الكل</a>
        @foreach ($categories as $cat)
            <a href="{{ $chipUrl($cat['value']) }}" @class(['ip-tabs__tab', 'is-active' => $filters['category'] === $cat['value']])>
                <i class="ti {{ $cat['icon'] }}"></i> {{ $cat['label'] }} ({{ $cat['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" class="ip-filters">
        <input type="hidden" name="category" value="{{ $filters['category'] }}">
        <div class="ip-field" style="flex:1; min-width:200px;">
            <span class="ip-field__label">بحث</span>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="ip-input" placeholder="ابحث في المستندات...">
        </div>
        <button type="submit" class="ip-btn">بحث</button>
    </form>

    @if ($documents->isEmpty())
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-folder-off"
                title="لا توجد مستندات بعد"
                description="ستظهر هنا مستنداتك من العقود وكشوف التوزيعات ومستندات التحقق فور توفرها.">
                <x-slot:action>
                    <a href="{{ route('contracts.index') }}" class="ip-btn">استعرض العقود الاستثمارية</a>
                </x-slot:action>
            </x-ip.empty-state>
        </x-ip.card>
    @else
        <div class="ip-doc-grid">
            @foreach ($documents as $document)
                <x-ip.document-card
                    :icon="$document->category->icon()"
                    :title="$document->title"
                    :category="$document->category->label()"
                    :color="$document->category->color()"
                    :date="$document->created_at?->format('Y-m-d')"
                    :size="$document->size_for_humans"
                    :url="$document->download_url" />
            @endforeach
        </div>

        <div class="ip-pagination">{{ $documents->onEachSide(1)->links() }}</div>
    @endif
@endsection
