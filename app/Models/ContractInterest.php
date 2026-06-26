<?php

namespace App\Models;

use App\Enums\ContractInterestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractInterest extends Model
{
    protected $fillable = [
        'user_id',
        'contract_id',
        'notes',
        'status',
        'contacted_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractInterestStatus::class,
            'contacted_at' => 'datetime',
            'converted_at' => 'datetime',
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
     * Interests still in play (pending or contacted).
     *
     * @param  Builder<ContractInterest>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ContractInterestStatus::activeValues());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->status->label());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function statusColor(): Attribute
    {
        return Attribute::get(fn (): string => $this->status->color());
    }
}
