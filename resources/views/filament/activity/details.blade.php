@php
    use App\Filament\Resources\ActivityResource;
@endphp

<div style="display:flex; flex-direction:column; gap:14px; font-family: var(--font-sans); color: var(--asas-text);">
    <dl style="display:grid; grid-template-columns: 120px 1fr; gap:10px 14px; font-size:14px; margin:0;">
        <dt style="color: var(--asas-text-muted);">الحدث</dt>
        <dd style="margin:0;">{{ ['created' => 'إنشاء', 'updated' => 'تحديث', 'deleted' => 'حذف'][$activity->event] ?? ($activity->event ?? '—') }}</dd>

        <dt style="color: var(--asas-text-muted);">الوصف</dt>
        <dd style="margin:0;">{{ $activity->description ?? '—' }}</dd>

        <dt style="color: var(--asas-text-muted);">الكيان</dt>
        <dd style="margin:0;">{{ ActivityResource::subjectLabel($activity->subject_type) }}</dd>

        <dt style="color: var(--asas-text-muted);">رقم الكيان</dt>
        <dd style="margin:0;">{{ $activity->subject_id ?? '—' }}</dd>

        <dt style="color: var(--asas-text-muted);">المنفّذ</dt>
        <dd style="margin:0;">{{ $activity->causer?->name ?? 'النظام' }}</dd>

        <dt style="color: var(--asas-text-muted);">التاريخ</dt>
        <dd style="margin:0;">{{ $activity->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
    </dl>

    <div>
        <div style="font-size:13px; color: var(--asas-text-muted); margin-bottom:6px;">الخصائص (Properties)</div>
        <div x-data="{ copied: false, copy() { navigator.clipboard.writeText($refs.json.innerText); this.copied = true; setTimeout(() => this.copied = false, 1500); } }"
             style="position:relative;">
            <button type="button" @click="copy()"
                    style="position:absolute; inset-inline-start:8px; top:8px; font-size:12px; padding:3px 10px; border-radius:6px; border:1px solid var(--asas-border); background: var(--asas-card-bg); color: var(--asas-text-secondary); cursor:pointer;">
                <span x-show="! copied">نسخ</span>
                <span x-show="copied" style="color: var(--asas-success-500);">تم النسخ ✓</span>
            </button>
            <pre x-ref="json" dir="ltr"
                 style="margin:0; padding:14px; background: var(--asas-app-bg); border:1px solid var(--asas-border); border-radius: var(--border-radius-md, 8px); font-family: var(--font-mono, monospace); font-size:12.5px; line-height:1.6; color: var(--asas-text); overflow:auto; max-height:320px; text-align:left;">{{ $json }}</pre>
        </div>
    </div>
</div>
