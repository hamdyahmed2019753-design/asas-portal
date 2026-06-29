<?php

namespace App\Services\Portal;

use App\Enums\KycState;
use App\Models\User;

/**
 * Resolves the investor's single highest-priority "next step" — one action, one
 * destination — so the dashboard can always surface exactly one clear CTA.
 *
 * Pure by design: reads only already-loaded user attributes plus the caller's
 * pre-computed hasInvestments flag, so it adds ZERO queries to the dashboard.
 */
class NextStepService
{
    /**
     * @return array{title: string, body: string, cta: string, url: string, icon: string, tone: string}|null
     */
    public function resolve(User $user, bool $hasInvestments): ?array
    {
        if (! $user->hasCompletedOnboarding()) {
            return [
                'title' => 'أكمل تسجيلك',
                'body' => 'أكمل بياناتك وارفع مستنداتك لتفعيل حسابك والبدء في الاستثمار.',
                'cta' => 'إكمال التسجيل',
                'url' => route('portal.onboarding'),
                'icon' => 'ti-rosette-discount-check',
                'tone' => 'warning',
            ];
        }

        return match ($user->kyc_state) {
            KycState::Rejected => [
                'title' => 'مستنداتك تحتاج إعادة رفع',
                'body' => $user->kyc_rejection_reason
                    ? 'سبب الرفض: '.$user->kyc_rejection_reason
                    : 'تم رفض مستنداتك. أعد رفعها لإكمال التحقق من هويتك.',
                'cta' => 'إعادة رفع المستندات',
                'url' => route('portal.kyc.resubmit'),
                'icon' => 'ti-file-alert',
                'tone' => 'danger',
            ],
            KycState::DocumentsUploaded, KycState::UnderReview => [
                'title' => 'مستنداتك قيد المراجعة',
                'body' => 'سنخطرك فور اكتمال التحقق. يمكنك تصفّح العقود المتاحة في الأثناء.',
                'cta' => 'تصفّح العقود',
                'url' => route('contracts.index'),
                'icon' => 'ti-clock-hour-4',
                'tone' => 'info',
            ],
            KycState::Approved => $hasInvestments ? null : [
                'title' => 'حسابك جاهز للاستثمار',
                'body' => 'تم تأكيد حسابك. استكشف العقود المتاحة وابدأ مشاركتك الأولى.',
                'cta' => 'استكشف العقود',
                'url' => route('contracts.index'),
                'icon' => 'ti-rocket',
                'tone' => 'success',
            ],
            default => null,
        };
    }
}
