<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'title',
        'disk',
        'path',
        'size',
        'original_name',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable file size (e.g. "1.2 MB").
     *
     * @return Attribute<string, never>
     */
    protected function sizeForHumans(): Attribute
    {
        return Attribute::get(function (): string {
            $bytes = (int) ($this->size ?? 0);
            if ($bytes <= 0) {
                return '—';
            }

            $units = ['B', 'KB', 'MB', 'GB'];
            $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

            return round($bytes / (1024 ** $power), 1).' '.$units[$power];
        });
    }
}
