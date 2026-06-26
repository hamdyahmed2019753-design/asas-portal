<?php

namespace App\Services\Portal;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The investor notification center. Every read and write is scoped through the
 * user's own notifications relationship — never a global query — so a user only
 * ever sees or touches their own. The unread count is cached for the nav bell.
 */
class NotificationCenterService
{
    private const UNREAD_TTL = 60; // seconds

    /**
     * Filtered, searched, paginated notifications + facets for the page.
     *
     * @return array<string, mixed>
     */
    public function data(User $user, Request $request): array
    {
        $category = $this->validCategory($request->query('category'));
        $status = in_array($request->query('status'), ['read', 'unread'], true) ? $request->query('status') : null;
        $search = trim((string) $request->query('q', ''));

        $query = $user->notifications();

        if ($category !== null) {
            $query->where('data->category', $category);
        }
        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }
        if ($search !== '') {
            $query->where(fn ($w) => $w
                ->where('data->title', 'like', "%{$search}%")
                ->orWhere('data->body', 'like', "%{$search}%"));
        }

        /** @var LengthAwarePaginator $notifications */
        $notifications = $query->latest()->paginate(15)->withQueryString();

        // One pass over the user's notifications for all facet counts.
        $all = $user->notifications()->get(['id', 'data', 'read_at']);

        return [
            'notifications' => $notifications,
            'groups' => $this->groupByDate($notifications->getCollection()),
            'categories' => $this->categoryFacets($all),
            'counts' => [
                'total' => $all->count(),
                'unread' => $all->whereNull('read_at')->count(),
                'read' => $all->whereNotNull('read_at')->count(),
            ],
            'filters' => ['category' => $category, 'status' => $status, 'q' => $search],
        ];
    }

    /**
     * Cached unread count for the navigation bell badge.
     */
    public function unreadCount(User $user): int
    {
        return Cache::remember($this->cacheKey($user), self::UNREAD_TTL, fn (): int => $user->unreadNotifications()->count());
    }

    public function markRead(User $user, string $id): void
    {
        $user->notifications()->whereKey($id)->first()?->markAsRead();
        $this->forgetUnread($user);
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
        $this->forgetUnread($user);
    }

    public function forgetUnread(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return "notif.unread.{$user->id}";
    }

    private function validCategory(mixed $value): ?string
    {
        return in_array($value, array_column(NotificationCategory::cases(), 'value'), true) ? $value : null;
    }

    /**
     * Per-category facet list (value, label, icon, count) for the filter chips.
     *
     * @param  Collection<int, DatabaseNotification>  $all
     * @return array<int, array<string, mixed>>
     */
    private function categoryFacets(Collection $all): array
    {
        $byCategory = $all->groupBy(fn ($n) => $n->data['category'] ?? NotificationCategory::System->value);

        return collect(NotificationCategory::cases())
            ->map(fn (NotificationCategory $c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'icon' => $c->icon(),
                'count' => $byCategory->get($c->value)?->count() ?? 0,
            ])
            ->filter(fn (array $c) => $c['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * Group a page of notifications by relative date band.
     *
     * @param  Collection<int, DatabaseNotification>  $items
     * @return array<int, array{label: string, items: Collection<int, mixed>}>
     */
    private function groupByDate(Collection $items): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();

        $bands = [
            'اليوم' => $items->filter(fn ($n) => $n->created_at?->isSameDay($today)),
            'هذا الأسبوع' => $items->filter(fn ($n) => $n->created_at && ! $n->created_at->isSameDay($today) && $n->created_at->gte($weekStart)),
            'أقدم' => $items->filter(fn ($n) => $n->created_at && $n->created_at->lt($weekStart)),
        ];

        return collect($bands)
            ->filter(fn (Collection $group) => $group->isNotEmpty())
            ->map(fn (Collection $group, string $label) => ['label' => $label, 'items' => $group->values()])
            ->values()
            ->all();
    }
}
