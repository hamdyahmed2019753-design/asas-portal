@props([])

{{-- Unified table wrapper. Slots: header (thead row), default (tbody rows), empty. --}}
<div {{ $attributes->merge(['class' => 'ip-table-wrap']) }}>
    <table class="ip-table">
        @isset($header)<thead>{{ $header }}</thead>@endisset
        <tbody>{{ $slot }}</tbody>
    </table>
    @isset($empty)<div>{{ $empty }}</div>@endisset
</div>
