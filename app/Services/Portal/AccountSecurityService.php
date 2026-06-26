<?php

namespace App\Services\Portal;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * All account-settings and security reads/writes for the investor. Every query
 * is scoped to the authenticated user — never a global query, never a user id
 * from the request.
 */
class AccountSecurityService
{
    /**
     * Full settings-page payload.
     *
     * @return array<string, mixed>
     */
    public function data(User $user, ?string $currentSessionId): array
    {
        return [
            'user' => $user,
            'sessions' => $this->activeSessions($user, $currentSessionId),
            'loginHistory' => $this->loginHistory($user),
            'security' => $this->securityScore($user),
        ];
    }

    /**
     * Update the editable profile fields (never email or KYC fields).
     *
     * @param  array{name: string, phone: ?string, city: ?string, country: ?string}  $data
     */
    public function updateProfile(User $user, array $data): void
    {
        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
        ]);
    }

    /**
     * Set a new password (the current password is verified in the FormRequest).
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => Hash::make($newPassword)])->save();
    }

    /**
     * The user's active sessions (database session driver), newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function activeSessions(User $user, ?string $currentSessionId): Collection
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($s) => [
                'device' => $this->platform($s->user_agent),
                'browser' => $this->browser($s->user_agent),
                'ip' => $s->ip_address ?? '—',
                'lastActivity' => Carbon::createFromTimestamp($s->last_activity),
                'current' => $s->id === $currentSessionId,
            ]);
    }

    /**
     * Sign out of every other session for this user.
     */
    public function logoutOtherSessions(User $user, ?string $currentSessionId): int
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($currentSessionId, fn ($q) => $q->where('id', '!=', $currentSessionId))
            ->delete();
    }

    /**
     * Latest login-history records (default 20).
     *
     * @return Collection<int, UserLogin>
     */
    public function loginHistory(User $user, int $limit = 20): Collection
    {
        return $user->logins()->orderByDesc('logged_in_at')->limit($limit)->get();
    }

    /**
     * Account security score (0–100) over five weighted factors.
     *
     * @return array<string, mixed>
     */
    public function securityScore(User $user): array
    {
        $factors = [
            ['label' => 'كلمة مرور محمية', 'done' => filled($user->password)],
            ['label' => 'إكمال التسجيل', 'done' => $user->hasCompletedOnboarding()],
            ['label' => 'التحقق من الهوية (KYC)', 'done' => $user->kycApproved()],
            ['label' => 'رقم هاتف مُسجّل', 'done' => filled($user->phone)],
            ['label' => 'رفع المستندات', 'done' => $user->documents()->exists()],
        ];

        $done = collect($factors)->where('done', true)->count();
        $score = (int) round($done / count($factors) * 100);

        [$status, $color] = match (true) {
            $score >= 80 => ['ممتاز', 'success'],
            $score >= 50 => ['جيد', 'warning'],
            default => ['يحتاج تحسينًا', 'danger'],
        };

        return ['score' => $score, 'status' => $status, 'color' => $color, 'factors' => $factors];
    }

    private function browser(?string $ua): string
    {
        return match (true) {
            $ua === null => 'غير معروف',
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'متصفح آخر',
        };
    }

    private function platform(?string $ua): string
    {
        return match (true) {
            $ua === null => 'جهاز غير معروف',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'جهاز آخر',
        };
    }
}
