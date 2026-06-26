@props([
    'items' => [],
])

{{--
    Breadcrumb. `items` is an array of ['label' => string, 'url' => ?string].
    The last item is rendered as the current (non-link) crumb.
--}}
<nav {{ $attributes->merge(['class' => 'asas-breadcrumb']) }} aria-label="مسار التنقّل">
    @foreach ($items as $item)
        @unless ($loop->first)
            <span class="asas-breadcrumb__sep">@svg('heroicon-m-chevron-left', 'w-3 h-3')</span>
        @endunless

        @if (! empty($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="asas-breadcrumb__item">{{ $item['label'] }}</a>
        @else
            <span class="asas-breadcrumb__item asas-breadcrumb__item--current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
