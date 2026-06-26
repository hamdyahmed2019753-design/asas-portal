@props([
    'tabs' => [],
    'active' => 0,
    'links' => false,
])

@if ($links)
    {{-- Link mode: each tab is ['label' => .., 'url' => .., 'active' => bool] (query-string driven). --}}
    <div {{ $attributes->merge(['class' => 'ip-tabs']) }}>
        @foreach ($tabs as $tab)
            <a href="{{ $tab['url'] }}" @class(['ip-tabs__tab', 'is-active' => $tab['active'] ?? false])>{{ $tab['label'] }}</a>
        @endforeach
    </div>
@else
    {{-- Alpine mode: in-page toggle. Panels can use x-show="active === N" in the slot. --}}
    <div {{ $attributes }} x-data="{ active: {{ (int) $active }} }">
        <div class="ip-tabs">
            @foreach ($tabs as $i => $tab)
                <button type="button" class="ip-tabs__tab" :class="active === {{ $i }} && 'is-active'" @click="active = {{ $i }}">{{ $tab }}</button>
            @endforeach
        </div>
        @isset($slot)<div style="margin-top:14px;">{{ $slot }}</div>@endisset
    </div>
@endif
