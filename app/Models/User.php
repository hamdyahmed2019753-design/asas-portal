<?php

namespace App\Models;

use App\Enums\KycState;
use App\Enums\KycStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * Only fields the user edits about themselves are mass-assignable. KYC
     * state, document paths, onboarding/2FA flags and verification timestamps
     * are written exclusively by server-side workflows via forceFill — never
     * from request input — to remove any mass-assignment / privilege-escalation
     * surface.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'city',
        'country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kyc_status' => KycStatus::class,
            'kyc_state' => KycState::class,
            'kyc_submitted_at' => 'datetime',
            'kyc_reviewed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * Whether the investor's KYC has been approved.
     */
    public function kycApproved(): bool
    {
        return $this->kyc_state === KycState::Approved;
    }

    /**
     * Map a KYC document type to its stored private path (or null).
     */
    public function kycDocumentPath(string $type): ?string
    {
        return match ($type) {
            'identity' => $this->identity_document_path,
            'iban' => $this->iban_document_path,
            'address' => $this->address_document_path,
            default => null,
        };
    }

    /**
     * Whether the investor has finished the onboarding wizard.
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Restrict admin panel access to users with the `admin` role.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Post-login landing path: admins go to the Filament panel, everyone
     * else (investors and members) to the investor portal.
     */
    public function homePath(): string
    {
        return $this->hasRole('admin') ? '/admin' : '/portal';
    }

    /**
     * All investments submitted by this user.
     *
     * @return HasMany<Investment, $this>
     */
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * Contracts this user has flagged interest in.
     *
     * @return HasMany<ContractInterest, $this>
     */
    public function contractInterests(): HasMany
    {
        return $this->hasMany(ContractInterest::class);
    }

    /**
     * All payouts belonging to this user, through their investments.
     *
     * @return HasManyThrough<Payout, Investment, $this>
     */
    public function payouts(): HasManyThrough
    {
        return $this->hasManyThrough(Payout::class, Investment::class);
    }

    /**
     * Documents owned by this user (documents center).
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Successful login audit records for this user.
     *
     * @return HasMany<UserLogin, $this>
     */
    public function logins(): HasMany
    {
        return $this->hasMany(UserLogin::class);
    }
}
