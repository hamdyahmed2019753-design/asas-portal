@props([
    'points' => [],
    'color' => 'primary',
    'height' => 36,
])

{{--
    Dependency-free inline area sparkline for KPI cards. Geometry is computed in
    PHP; colours are token-interpolated. (Real ApexCharts sparklines may replace
    this inside concrete widgets, but this keeps the design system self-contained.)
--}}
@php
    $vals = array_values($points);
    $count = count($vals);
    $max = $count ? max($vals) : 1;
    $min = $count ? min($vals) : 0;
    $range = ($max - $min) ?: 1;
    $h = (int) $height;
    $w = 100;
    $step = $count > 1 ? $w / ($count - 1) : $w;
    $line = [];
    foreach ($vals as $i => $v) {
        $x = round($i * $step, 2);
        $y = round($h - (($v - $min) / $range) * ($h - 4) - 2, 2);
        $line[] = "{$x},{$y}";
    }
    $polyline = implode(' ', $line);
    $polygon = $count ? "0,{$h} {$polyline} {$w},{$h}" : '';
@endphp

<svg {{ $attributes }} viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none"
     width="100%" height="{{ $h }}" role="img" aria-label="مؤشر اتجاه">
    @if ($count)
        <polygon points="{{ $polygon }}" fill="var(--asas-{{ $color }}-50)"></polygon>
        <polyline points="{{ $polyline }}" fill="none" stroke="var(--asas-{{ $color }}-500)"
                  stroke-width="2" vector-effect="non-scaling-stroke"></polyline>
    @endif
</svg>
