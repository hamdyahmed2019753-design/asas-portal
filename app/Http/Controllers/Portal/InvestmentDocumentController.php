<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Payout;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Streams the investor's PDF documents. Every action authorises ownership
 * through the InvestmentPolicy before a single byte is generated, so a crafted
 * id can never download another investor's document.
 */
class InvestmentDocumentController extends Controller
{
    public function __construct(private readonly DocumentPdfService $pdf) {}

    public function contract(Investment $investment): Response
    {
        $this->authorize('view', $investment);

        return $this->stream($this->pdf->investmentContract($investment), "contract-{$investment->id}.pdf");
    }

    public function statement(Investment $investment): Response
    {
        $this->authorize('view', $investment);

        return $this->stream($this->pdf->accountStatement($investment), "statement-{$investment->id}.pdf");
    }

    public function certificate(Investment $investment): Response
    {
        $this->authorize('view', $investment);

        return $this->stream($this->pdf->investmentCertificate($investment), "certificate-{$investment->id}.pdf");
    }

    public function receipt(Payout $payout): SymfonyResponse
    {
        // Ownership via the parent investment; receipts exist only for paid payouts.
        $this->authorize('view', $payout->investment);
        abort_unless($payout->status === PayoutStatus::Paid, 404);

        // Prefer the admin-uploaded bank-transfer receipt; fall back to the
        // system-generated PDF (Feature 12) for payouts without one.
        if (filled($payout->receipt_path) && Storage::disk('local')->exists($payout->receipt_path)) {
            $ext = pathinfo($payout->receipt_path, PATHINFO_EXTENSION) ?: 'pdf';

            return Storage::disk('local')->download($payout->receipt_path, "receipt-{$payout->id}.{$ext}");
        }

        return $this->stream($this->pdf->payoutReceipt($payout), "receipt-{$payout->id}.pdf");
    }

    private function stream(string $pdf, string $filename): Response
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
