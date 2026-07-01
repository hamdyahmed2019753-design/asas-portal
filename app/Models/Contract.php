<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
        'activity_type',
        'expected_return',
        'target_amount',
        'min_amount',
        'max_amount',
        'share_price',
        'duration_months',
        'payouts_count',
        'payout_schedule',
        'status',
        'opens_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'expected_return' => 'decimal:2',
            'target_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'share_price' => 'decimal:2',
            'duration_months' => 'integer',
            'payouts_count' => 'integer',
            'payout_schedule' => 'array',
            'status' => ContractStatus::class,
            'opens_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Investment, $this>
     */
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * @return HasMany<ContractInterest, $this>
     */
    public function contractInterests(): HasMany
    {
        return $this->hasMany(ContractInterest::class);
    }

    /**
     * Contracts visible to the public landing page (upcoming or open).
     *
     * @param  Builder<Contract>  $query
     */
    public function scopePublicVisible(Builder $query): void
    {
        $query->whereIn('status', [
            ContractStatus::Upcoming->value,
            ContractStatus::Open->value,
        ]);
    }

    /**
     * Display-only label for the contract status (delegates to the enum).
     *
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->status->label());
    }

    /**
     * Display-only color token for the contract status (delegates to the enum).
     *
     * @return Attribute<string, never>
     */
    protected function statusColor(): Attribute
    {
        return Attribute::get(fn (): string => $this->status->color());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'activity_type',
                'expected_return',
                'target_amount',
                'min_amount',
                'max_amount',
                'duration_months',
                'payouts_count',
                'status',
                'opens_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
