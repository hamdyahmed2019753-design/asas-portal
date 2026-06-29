<?php

namespace App\Services\Portal;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\DocumentCategory;
use App\Enums\KycState;
use App\Filament\Resources\InvestorResource;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycRejectedNotification;
use App\Notifications\KycSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;

/**
 * The KYC workflow: builds the investor-facing read model (card, timeline,
 * documents) and performs the admin transitions (start review / approve /
 * reject). All document links are short-lived signed URLs; files never leave
 * private storage.
 */
class KycService
{
    /**
     * Document types and their Arabic labels.
     *
     * @var array<string, string>
     */
    public const DOCUMENTS = [
        'identity' => 'صورة الهوية',
        'iban' => 'شهادة الآيبان',
        'address' => 'إثبات العنوان',
    ];

    /**
     * Full KYC card view-model for the profile page.
     *
     * @return array<string, mixed>
     */
    public function card(User $user): array
    {
        $state = $user->kyc_state;

        return [
            'state' => $state,
            'submittedAt' => $user->kyc_submitted_at,
            'reviewedAt' => $user->kyc_reviewed_at,
            'rejectionReason' => $user->kyc_rejection_reason,
            'progress' => $state?->progress() ?? 0,
            'timeline' => $this->timeline($user),
            'documents' => $this->documents($user),
            'canResubmit' => $this->canResubmit($user),
        ];
    }

    /**
     * Whether the user still needs attention (anything but approved).
     */
    public function needsAttention(User $user): bool
    {
        return $user->kyc_state !== KycState::Approved;
    }

    /**
     * Single source of truth gating every investment-related action.
     */
    public function canInvest(User $user): bool
    {
        return $user->kyc_state === KycState::Approved;
    }

    /**
     * A rejected investor may upload fresh documents.
     */
    public function canResubmit(User $user): bool
    {
        return $user->kyc_state === KycState::Rejected;
    }

    /**
     * Three-stage KYC timeline (upload → review → result).
     *
     * @return array<int, array{title: string, date: ?string, color: string}>
     */
    private function timeline(User $user): array
    {
        $state = $user->kyc_state;
        $submitted = $user->kyc_submitted_at?->format('Y-m-d');
        $reviewed = $user->kyc_reviewed_at?->format('Y-m-d');

        $steps = [
            ['title' => 'رفع المستندات', 'date' => $submitted, 'color' => $submitted ? 'success' : 'gray'],
            ['title' => 'قيد المراجعة', 'date' => null, 'color' => $state && $state !== KycState::DocumentsUploaded ? 'success' : 'gray'],
        ];

        if ($state === KycState::Approved) {
            $steps[] = ['title' => 'تم التحقق من الحساب', 'date' => $reviewed, 'color' => 'success'];
        } elseif ($state === KycState::Rejected) {
            $steps[] = ['title' => 'تم رفض التحقق', 'date' => $reviewed, 'color' => 'danger'];
        } else {
            $steps[] = ['title' => 'النتيجة', 'date' => null, 'color' => 'gray'];
        }

        return $steps;
    }

    /**
     * Uploaded documents with short-lived signed download URLs (owner only).
     *
     * @return array<int, array{type: string, label: string, url: string}>
     */
    private function documents(User $user): array
    {
        $docs = [];

        foreach (self::DOCUMENTS as $type => $label) {
            if (filled($user->kycDocumentPath($type))) {
                $docs[] = [
                    'type' => $type,
                    'label' => $label,
                    'url' => URL::temporarySignedRoute('portal.kyc.document', now()->addMinutes(10), ['type' => $type]),
                ];
            }
        }

        return $docs;
    }

    // ----- Admin transitions -----

    public function startReview(User $user): void
    {
        $this->transition($user, KycState::UnderReview, reviewedAt: false);
    }

    public function approve(User $user): void
    {
        $this->transition($user, KycState::Approved, reason: null);
        $user->notify(new KycApprovedNotification);

        NotifyAdmins::send(new AdminNotification(
            title: 'اعتماد تحقق المستثمر',
            body: "تم اعتماد تحقيق هوية «{$user->name}».",
            category: AdminNotificationCategory::Kyc,
            priority: AdminNotificationPriority::Medium,
            actor: $user,
            target: $user,
            url: InvestorResource::getUrl('index'),
            actionLabel: 'فتح المستثمر',
        ));
    }

    public function reject(User $user, string $reason): void
    {
        $this->transition($user, KycState::Rejected, reason: $reason);
        $user->notify(new KycRejectedNotification($reason));

        NotifyAdmins::send(new AdminNotification(
            title: 'رفض تحقق المستثمر',
            body: "تم رفض تحقق «{$user->name}».",
            category: AdminNotificationCategory::Kyc,
            priority: AdminNotificationPriority::High,
            actor: $user,
            target: $user,
            url: InvestorResource::getUrl('index'),
            actionLabel: 'فتح المستثمر',
        ));
    }

    /**
     * A rejected investor re-uploads documents: rejected → documents_uploaded.
     *
     * @param  array{identity: UploadedFile, iban: UploadedFile, address: UploadedFile}  $files
     */
    public function resubmit(User $user, array $files): void
    {
        $dir = "onboarding/{$user->id}";

        // Replace the previous KYC documents in the documents center.
        $user->documents()->where('category', DocumentCategory::Kyc->value)->delete();

        $map = ['identity' => 'صورة الهوية', 'iban' => 'شهادة الآيبان', 'address' => 'إثبات العنوان'];
        $paths = [];
        foreach ($map as $key => $title) {
            $paths[$key] = $files[$key]->store($dir, 'local');
            $user->documents()->create([
                'category' => DocumentCategory::Kyc->value,
                'title' => $title,
                'disk' => 'local',
                'path' => $paths[$key],
                'size' => $files[$key]->getSize(),
                'original_name' => $files[$key]->getClientOriginalName(),
            ]);
        }

        $user->forceFill([
            'identity_document_path' => $paths['identity'],
            'iban_document_path' => $paths['iban'],
            'address_document_path' => $paths['address'],
            'kyc_state' => KycState::DocumentsUploaded,
            'kyc_status' => KycState::DocumentsUploaded->toStatus()->value,
            'kyc_submitted_at' => now(),
            'kyc_reviewed_at' => null,
            'kyc_rejection_reason' => null,
        ])->save();

        $user->notify(new KycSubmittedNotification);

        NotifyAdmins::send(new AdminNotification(
            title: 'إعادة رفع مستندات تحقق',
            body: "أعاد «{$user->name}» رفع مستندات التحقق في {$user->kyc_submitted_at?->format('Y-m-d H:i')} للمراجعة.",
            category: AdminNotificationCategory::Kyc,
            priority: AdminNotificationPriority::High,
            actor: $user,
            target: $user,
            url: InvestorResource::getUrl('index'),
            actionLabel: 'مراجعة التحقق',
        ));
    }

    private function transition(User $user, KycState $state, bool $reviewedAt = true, ?string $reason = null): void
    {
        $user->forceFill([
            'kyc_state' => $state,
            'kyc_status' => $state->toStatus()->value, // keep legacy column in sync
            'kyc_reviewed_at' => $reviewedAt ? now() : $user->kyc_reviewed_at,
            'kyc_rejection_reason' => $state === KycState::Rejected ? $reason : null,
        ])->save();
    }
}
