<?php

namespace App\Services\Portal;

use App\Models\NewsUpdate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * All queries for the investor news page. News is public-facing announcement
 * content, so it is not user-scoped — but only PUBLISHED items are ever read.
 */
class NewsPortalService
{
    /**
     * Published news, newest first, paginated 10 per page.
     *
     * @return array{news: LengthAwarePaginator<int, NewsUpdate>}
     */
    public function data(): array
    {
        return [
            'news' => NewsUpdate::query()
                ->published()
                ->orderByDesc('published_at')
                ->paginate(10),
        ];
    }
}
