<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Portal\KycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private KYC documents. Access is gated three ways at once:
 *  - the route requires a valid temporary SIGNED url,
 *  - the request must be authenticated,
 *  - a policy check confirms the caller owns the file (or is an admin).
 * Files are streamed straight from the private disk — never publicly exposed.
 */
class KycDocumentController extends Controller
{
    public function __construct(private readonly KycService $kyc) {}

    /**
     * The authenticated investor downloads one of their own documents.
     */
    public function own(Request $request, string $type): StreamedResponse
    {
        return $this->stream($request->user(), $request->user(), $type);
    }

    /**
     * An admin downloads a specific investor's document.
     */
    public function admin(Request $request, User $user, string $type): StreamedResponse
    {
        return $this->stream($request->user(), $user, $type);
    }

    private function stream(User $caller, User $owner, string $type): StreamedResponse
    {
        $this->authorize('viewKycDocuments', $owner);

        abort_unless(array_key_exists($type, KycService::DOCUMENTS), 404);

        $path = $owner->kycDocumentPath($type);
        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, KycService::DOCUMENTS[$type].'.'.pathinfo($path, PATHINFO_EXTENSION));
    }
}
