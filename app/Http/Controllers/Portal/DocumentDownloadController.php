<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a private document. Access is gated three ways at once:
 *  - the route requires a valid temporary SIGNED url,
 *  - the request must be authenticated,
 *  - a policy check confirms the caller owns the document (or is an admin).
 * The file is streamed straight from its private disk; the path is never exposed.
 */
class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name ?? ($document->title.'.pdf')
        );
    }
}
