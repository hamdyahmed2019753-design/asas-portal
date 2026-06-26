@php
    $event = $getState();
    $labels = ['created' => 'إنشاء', 'updated' => 'تحديث', 'deleted' => 'حذف'];
    $known = is_string($event) && array_key_exists($event, $labels);
@endphp

{{-- Activity event badge. Colour comes from the --asas-activity-{event} tokens
     (created→success, updated→info, deleted→danger). No hardcoded colours. --}}
@if ($known)
    <span class="asas-badge"
          style="--ev: var(--asas-activity-{{ $event }}); background: color-mix(in srgb, var(--ev) 14%, transparent); color: var(--ev);">
        <span class="asas-badge__dot" style="background: var(--ev);"></span>{{ $labels[$event] }}
    </span>
@else
    <span class="asas-badge" style="background: var(--asas-gray-50); color: var(--asas-gray-700);">
        {{ $event ?? '—' }}
    </span>
@endif
