<?php

namespace App\Services\Portal;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * The investor documents center. Every read is scoped through the user's own
 * documents relationship — never a global query. Each listed document carries a
 * short-lived signed download URL; raw paths are never exposed to the view.
 */
class DocumentsCenterService
{
    /**
     * Filtered, searched, paginated documents + summary/facets for the page.
     *
     * @return array<string, mixed>
     */
    public function data(User $user, Request $request): array
    {
        $category = $this->validCategory($request->query('category'));
        $search = trim((string) $request->query('q', ''));

        $query = $user->documents();

        if ($category !== null) {
            $query->where('category', $category);
        }
        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        /** @var LengthAwarePaginator $documents */
        $documents = $query->latest()->paginate(12)->withQueryString();

        // Attach a signed download URL to each item (no path ever reaches Blade).
        $documents->getCollection()->each(fn (Document $d) => $d->setAttribute('download_url', $this->signedUrl($d)));

        // One light pass for summary + facets.
        $all = $user->documents()->get(['id', 'category', 'title', 'created_at']);
        $last = $all->sortByDesc('created_at')->first();

        return [
            'documents' => $documents,
            'summary' => [
                'total' => $all->count(),
                'lastTitle' => $last?->title,
                'lastDate' => $last?->created_at?->format('Y-m-d'),
            ],
            'categories' => $this->categoryFacets($all),
            'filters' => ['category' => $category, 'q' => $search],
        ];
    }

    /**
     * Short-lived signed download URL for a single document.
     */
    public function signedUrl(Document $document): string
    {
        return URL::temporarySignedRoute('documents.download', now()->addMinutes(10), ['document' => $document->id]);
    }

    private function validCategory(mixed $value): ?string
    {
        return in_array($value, array_column(DocumentCategory::cases(), 'value'), true) ? $value : null;
    }

    /**
     * Per-category facet list (value, label, icon, count) for the filter chips.
     *
     * @param  Collection<int, Document>  $all
     * @return array<int, array<string, mixed>>
     */
    private function categoryFacets(Collection $all): array
    {
        $byCategory = $all->groupBy(fn (Document $d) => $d->category->value);

        return collect(DocumentCategory::cases())
            ->map(fn (DocumentCategory $c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'icon' => $c->icon(),
                'count' => $byCategory->get($c->value)?->count() ?? 0,
            ])
            ->filter(fn (array $c) => $c['count'] > 0)
            ->values()
            ->all();
    }
}
