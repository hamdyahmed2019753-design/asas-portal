@props([
    'steps' => [],
    'current' => 1,
    'progress' => 0,
])

{{-- Presentational only: the parent decides labels, current step, and %. --}}
<div {{ $attributes->merge(['class' => 'ip-wizard']) }}>
    <div class="ip-wizard__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
        <span class="ip-wizard__fill" style="width: {{ $progress }}%;"></span>
    </div>

    <ol class="ip-wizard__steps">
        @foreach ($steps as $i => $label)
            @php $n = $i + 1; @endphp
            <li @class([
                'ip-wizard__step',
                'is-done' => $n < $current,
                'is-active' => $n === $current,
            ])>
                <span class="ip-wizard__num">
                    @if ($n < $current)<i class="ti ti-check"></i>@else{{ $n }}@endif
                </span>
                <span class="ip-wizard__label">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</div>
