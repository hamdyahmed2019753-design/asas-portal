@props([
    'title' => '',
    'excerpt' => null,
    'publishedDate' => null,
])

<div {{ $attributes->merge(['class' => 'ip-news']) }}>
    <div class="ip-news__title">{{ $title }}</div>
    @if ($excerpt)<div class="ip-news__excerpt">{{ $excerpt }}</div>@endif
    @if ($publishedDate)<div class="ip-news__date"><i class="ti ti-calendar"></i> {{ $publishedDate }}</div>@endif
</div>
