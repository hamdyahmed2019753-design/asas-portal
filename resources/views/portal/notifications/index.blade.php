@extends('layouts.portal')

@section('title', 'الإشعارات')

@section('content')
    <x-ip.page-header title="الإشعارات" subtitle="مركز إشعاراتك: كل ما يخص حسابك من تنبيهات وتحديثات.">
        @if ($counts['unread'] > 0)
            <x-slot:actions>
                <form method="POST" action="{{ route('portal.notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="ip-btn">تعليم الكل كمقروء</button>
                </form>
            </x-slot:actions>
        @endif
    </x-ip.page-header>

    {{-- Counts --}}
    <div class="ip-grid ip-grid--3">
        <x-ip.stat-card color="primary" icon="ti-bell" label="إجمالي الإشعارات" :value="$counts['total']" />
        <x-ip.stat-card color="warning" icon="ti-bell-ringing" label="غير المقروءة" :value="$counts['unread']" />
        <x-ip.stat-card color="success" icon="ti-bell-check" label="المقروءة" :value="$counts['read']" />
    </div>

    {{-- Category chips (query-string) --}}
    @php
        $chipUrl = fn ($cat) => route('portal.notifications', array_merge(request()->only(['status', 'q']), array_filter(['category' => $cat])));
    @endphp
    <div class="ip-tabs" style="margin:20px 0 14px; flex-wrap:wrap;">
        <a href="{{ $chipUrl(null) }}" @class(['ip-tabs__tab', 'is-active' => $filters['category'] === null])>الكل</a>
        @foreach ($categories as $cat)
            <a href="{{ $chipUrl($cat['value']) }}" @class(['ip-tabs__tab', 'is-active' => $filters['category'] === $cat['value']])>
                <i class="ti {{ $cat['icon'] }}"></i> {{ $cat['label'] }} ({{ $cat['count'] }})
            </a>
        @endforeach
    </div>

    {{-- Filters: status + search --}}
    <form method="GET" class="ip-filters">
        <input type="hidden" name="category" value="{{ $filters['category'] }}">
        <div class="ip-field">
            <span class="ip-field__label">الحالة</span>
            <select name="status" class="ip-select" onchange="this.form.submit()">
                <option value="">الكل</option>
                <option value="unread" @selected($filters['status'] === 'unread')>غير المقروءة</option>
                <option value="read" @selected($filters['status'] === 'read')>المقروءة</option>
            </select>
        </div>
        <div class="ip-field" style="flex:1; min-width:200px;">
            <span class="ip-field__label">بحث</span>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="ip-input" placeholder="ابحث في الإشعارات...">
        </div>
        <button type="submit" class="ip-btn">بحث</button>
    </form>

    @if ($notifications->isEmpty())
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-bell-off"
                title="لا توجد إشعارات"
                description="ستظهر هنا التنبيهات المتعلقة بحسابك ومشاركاتك وتوزيعاتك." />
        </x-ip.card>
    @else
        @foreach ($groups as $group)
            <x-ip.section-header :title="$group['label']" />
            <x-ip.card style="padding:0;">
                @foreach ($group['items'] as $notification)
                    <x-ip.notification-item
                        :title="$notification->data['title'] ?? 'إشعار'"
                        :description="$notification->data['body'] ?? null"
                        :date="$notification->created_at?->translatedFormat('Y-m-d H:i')"
                        :unread="is_null($notification->read_at)">
                        @if (is_null($notification->read_at))
                            <x-slot:action>
                                <form method="POST" action="{{ route('portal.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="ip-notif__action">تعليم كمقروء</button>
                                </form>
                            </x-slot:action>
                        @endif
                    </x-ip.notification-item>
                @endforeach
            </x-ip.card>
        @endforeach

        <div class="ip-pagination">{{ $notifications->onEachSide(1)->links() }}</div>
    @endif
@endsection
