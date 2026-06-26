<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class NewsUpdate extends Model
{
    protected $fillable = [
        'title',
        'body',
        'published_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Published news items whose publish date has arrived.
     *
     * Requires BOTH is_published = true AND published_at <= now().
     * A published item without a published_at date is not yet visible.
     *
     * @param  Builder<NewsUpdate>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Display-only publish-status label (منشور / مسودة).
     *
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_published ? 'منشور' : 'مسودة');
    }

    /**
     * Display-only publish-status color token, consumed by the badge component.
     *
     * @return Attribute<string, never>
     */
    protected function statusColor(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_published ? 'success' : 'gray');
    }
}
