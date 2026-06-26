<?php

namespace App\Enums;

/**
 * The full investor-facing KYC workflow (richer than the legacy KycStatus).
 *
 * documents_uploaded → under_review → approved
 *                                   ↘ rejected
 */
enum KycState: string
{
    case DocumentsUploaded = 'documents_uploaded';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DocumentsUploaded => 'تم رفع المستندات',
            self::UnderReview => 'قيد المراجعة',
            self::Approved => 'تم التحقق',
            self::Rejected => 'مرفوض',
        };
    }

    /**
     * Portal --ip-* colour token.
     */
    public function color(): string
    {
        return match ($this) {
            self::DocumentsUploaded => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DocumentsUploaded => 'ti-file-upload',
            self::UnderReview => 'ti-clock-hour-4',
            self::Approved => 'ti-rosette-discount-check',
            self::Rejected => 'ti-alert-triangle',
        };
    }

    /**
     * Banner sentence shown on the profile KYC card.
     */
    public function message(): string
    {
        return match ($this) {
            self::DocumentsUploaded => 'تم استلام مستنداتك، وستبدأ المراجعة قريبًا.',
            self::UnderReview => 'مستنداتك قيد المراجعة من قبل فريق أساس.',
            self::Approved => 'تم التحقق من حسابك بنجاح.',
            self::Rejected => 'تم رفض التحقق، يرجى مراجعة السبب أدناه والتواصل مع الإدارة.',
        };
    }

    /**
     * Progress-bar percentage for the KYC card.
     */
    public function progress(): int
    {
        return match ($this) {
            self::DocumentsUploaded => 33,
            self::UnderReview => 66,
            self::Approved, self::Rejected => 100,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Approved || $this === self::Rejected;
    }

    /**
     * Keep the legacy KycStatus column meaningful for admin widgets/columns.
     */
    public function toStatus(): KycStatus
    {
        return match ($this) {
            self::Approved => KycStatus::Verified,
            self::Rejected => KycStatus::Rejected,
            default => KycStatus::Pending,
        };
    }
}
