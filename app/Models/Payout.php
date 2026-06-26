<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payout extends Model
{
    use LogsActivity;

    protected $fillable = [
        'investment_id',
        'type',
        'sequence',
        'due_date',
        'amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayoutType::class,
            'sequence' => 'integer',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'status' => PayoutStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Investment, $this>
     */
    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    /**
     * Profit-type payouts only.
     *
     * @param  Builder<Payout>  $query
     */
    public function scopeProfit(Builder $query): void
    {
        $query->where('type', PayoutType::Profit->value);
    }

    /**
     * Capital-return payouts only.
     *
     * @param  Builder<Payout>  $query
     */
    public function scopeCapital(Builder $query): void
    {
        $query->where('type', PayoutType::Capital->value);
    }

    /**
     * Payouts already marked as paid.
     *
     * @param  Builder<Payout>  $query
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PayoutStatus::Paid->value);
    }

    /**
     * Upcoming payouts (scheduled or due, not yet paid).
     *
     * @param  Builder<Payout>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereIn('status', [
            PayoutStatus::Scheduled->value,
            PayoutStatus::Due->value,
        ]);
    }

    /**
     * Display-only label for the payout type (delegates to the enum).
     *
     * @return Attribute<string, never>
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->type->label());
    }

    /**
     * Display-only label for the payout status (delegates to the enum).
     *
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->status->label());
    }

    /**
     * Display-only color token for the payout status (delegates to the enum).
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
                'type',
                'sequence',
                'due_date',
                'amount',
                'status',
                'paid_at',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
