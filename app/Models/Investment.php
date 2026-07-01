<?php

namespace App\Models;

use App\Enums\InvestmentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Investment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'contract_id',
        'amount',
        'shares',
        'receipt_path',
        'payment_proof_path',
        'payment_method',
        'status',
        'start_date',
        'end_date',
        'approved_at',
        'payment_confirmed_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'shares' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => InvestmentStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
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
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Only approved investments.
     *
     * @param  Builder<Investment>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', InvestmentStatus::Approved->value);
    }

    /**
     * Display-only label for the investment status (delegates to the enum).
     *
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->status->label());
    }

    /**
     * Display-only color token for the investment status (delegates to the enum).
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
                'amount',
                'status',
                'start_date',
                'end_date',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
