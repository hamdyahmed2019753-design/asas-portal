<?php

namespace App\Services\Portal;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\ContractInterestStatus;
use App\Exceptions\DuplicateInterestException;
use App\Filament\Resources\InvestorResource;
use App\Models\Contract;
use App\Models\ContractInterest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\ContractInterestNotification;

/**
 * All persistence and queries for the contract-interest workflow. Controllers
 * stay thin; investor reads are scoped through the user's own relationship.
 */
class ContractInterestService
{
    /**
     * Investor expresses interest in a contract. Guards against a duplicate
     * active (pending/contacted) interest on the same contract.
     *
     * @throws DuplicateInterestException
     */
    public function express(User $user, Contract $contract, ?string $notes = null): ContractInterest
    {
        if ($this->hasActiveInterest($user, $contract)) {
            throw new DuplicateInterestException;
        }

        $interest = $user->contractInterests()->create([
            'contract_id' => $contract->id,
            'notes' => $notes,
            'status' => ContractInterestStatus::Pending->value,
        ]);

        $user->notify(new ContractInterestNotification('submitted', $contract->title));

        NotifyAdmins::send(new AdminNotification(
            title: 'اهتمام جديد بعقد',
            body: "عبّر «{$user->name}» عن اهتمامه بعقد «{$contract->title}» في ".now()->format('Y-m-d H:i').'.',
            category: AdminNotificationCategory::Interest,
            priority: AdminNotificationPriority::Medium,
            actor: $user,
            target: $interest,
            url: InvestorResource::getUrl('view', ['record' => $user]),
            actionLabel: 'فتح المستثمر',
        ));

        return $interest;
    }

    /**
     * Whether the user already has an open (pending/contacted) interest here.
     */
    public function hasActiveInterest(User $user, Contract $contract): bool
    {
        return $user->contractInterests()
            ->where('contract_id', $contract->id)
            ->active()
            ->exists();
    }

    /**
     * The user's latest interest for a given contract (or null).
     */
    public function forContract(User $user, Contract $contract): ?ContractInterest
    {
        return $user->contractInterests()
            ->where('contract_id', $contract->id)
            ->latest()
            ->first();
    }

    /**
     * Count of the user's pending interests (dashboard indicator).
     */
    public function pendingCount(User $user): int
    {
        return $user->contractInterests()
            ->where('status', ContractInterestStatus::Pending->value)
            ->count();
    }

    // ----- Admin transitions -----

    public function markContacted(ContractInterest $interest): void
    {
        $interest->forceFill([
            'status' => ContractInterestStatus::Contacted->value,
            'contacted_at' => now(),
        ])->save();

        $interest->user->notify(new ContractInterestNotification('contacted', $interest->contract->title));
    }

    public function reject(ContractInterest $interest): void
    {
        $interest->forceFill([
            'status' => ContractInterestStatus::Rejected->value,
        ])->save();

        $interest->user->notify(new ContractInterestNotification('rejected', $interest->contract->title));
    }
}
