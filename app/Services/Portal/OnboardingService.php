<?php

namespace App\Services\Portal;

use App\Enums\DocumentCategory;
use App\Enums\KycState;
use App\Models\User;
use App\Notifications\KycSubmittedNotification;
use Illuminate\Http\UploadedFile;

/**
 * Drives the investor onboarding wizard. All step logic and persistence live
 * here — controllers stay thin and Blade stays presentational. Everything acts
 * on the authenticated user's own record (no cross-user access is possible).
 */
class OnboardingService
{
    public const STEPS = 4;

    /**
     * The first step the user still needs to complete (1..3), or 4 once done.
     */
    public function currentStep(User $user): int
    {
        if (! $this->profileComplete($user)) {
            return 1;
        }

        if (! $this->documentsComplete($user)) {
            return 2;
        }

        if ($user->terms_accepted_at === null) {
            return 3;
        }

        return 4;
    }

    /**
     * Completion percentage for the progress bar (0..100). There are three
     * actionable steps (profile, documents, terms); the fourth is the success
     * screen, at which point progress is full.
     */
    public function progress(User $user): int
    {
        $current = $this->currentStep($user);

        if ($current >= self::STEPS) {
            return 100;
        }

        return (int) round((($current - 1) / (self::STEPS - 1)) * 100);
    }

    /**
     * View payload for the wizard.
     *
     * @return array<string, mixed>
     */
    public function data(User $user, ?int $requestedStep = null): array
    {
        $current = $this->currentStep($user);
        $step = $requestedStep !== null && $requestedStep >= 1 && $requestedStep <= $current
            ? $requestedStep
            : $current;

        return [
            'user' => $user,
            'step' => $step,
            'currentStep' => $current,
            'progress' => $this->progress($user),
            'completed' => $user->hasCompletedOnboarding(),
        ];
    }

    /**
     * Step 1 — persist the profile details.
     *
     * @param  array{name: string, phone: string, city: string, country: string}  $data
     */
    public function saveProfile(User $user, array $data): void
    {
        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'country' => $data['country'],
        ]);
    }

    /**
     * Step 2 — store the three uploaded documents on the private disk.
     *
     * @param  array{identity: UploadedFile, iban: UploadedFile, address: UploadedFile}  $files
     */
    public function saveDocuments(User $user, array $files): void
    {
        $dir = "onboarding/{$user->id}";

        $map = [
            'identity' => ['column' => 'identity_document_path', 'title' => 'صورة الهوية'],
            'iban' => ['column' => 'iban_document_path', 'title' => 'شهادة الآيبان'],
            'address' => ['column' => 'address_document_path', 'title' => 'إثبات العنوان'],
        ];

        $updates = [];
        foreach ($map as $key => $meta) {
            $path = $files[$key]->store($dir, 'local');
            $updates[$meta['column']] = $path;
            $this->recordKycDocument($user, $meta['title'], $path, $files[$key]);
        }

        // Document paths are server-generated, not user-assignable → forceFill.
        $user->forceFill($updates)->save();
    }

    /**
     * Register an uploaded KYC file in the documents center.
     */
    private function recordKycDocument(User $user, string $title, string $path, UploadedFile $file): void
    {
        $user->documents()->create([
            'category' => DocumentCategory::Kyc->value,
            'title' => $title,
            'disk' => 'local',
            'path' => $path,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Step 3 — accept the terms, finalise onboarding, and submit KYC for review.
     */
    public function complete(User $user): void
    {
        $user->forceFill([
            'terms_accepted_at' => now(),
            'onboarding_completed_at' => now(),
            'kyc_state' => KycState::DocumentsUploaded,
            'kyc_submitted_at' => now(),
        ])->save();

        $user->notify(new KycSubmittedNotification);
    }

    private function profileComplete(User $user): bool
    {
        return filled($user->name)
            && filled($user->phone)
            && filled($user->city)
            && filled($user->country);
    }

    private function documentsComplete(User $user): bool
    {
        return filled($user->identity_document_path)
            && filled($user->iban_document_path)
            && filled($user->address_document_path);
    }
}
