<?php

namespace App\Services\Pdf;

use App\Models\Investment;
use App\Models\Payout;
use App\Services\Portal\InvestmentPortalService;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Generates the investor's PDF documents (contract, statement, certificate,
 * payout receipt). Rendering is mPDF (native Arabic shaping + RTL); branding is
 * pulled from the Settings service so every document reflects the live identity.
 */
class DocumentPdfService
{
    public function __construct(private readonly InvestmentPortalService $investments) {}

    public function investmentContract(Investment $investment): string
    {
        $investment->loadMissing(['user', 'contract']);

        return $this->render('pdf.investment-contract', [
            'investment' => $investment,
            'return' => $this->returnLabel($investment),
            'reference' => 'INV-'.$investment->id,
        ]);
    }

    public function accountStatement(Investment $investment): string
    {
        $investment->loadMissing('user');
        $details = $this->investments->details($investment); // reuse the same figures as the UI

        return $this->render('pdf.account-statement', [
            'investment' => $details['investment'],
            'payouts' => $details['payouts']->values(),
            'profit' => $details['profit'],
            'reference' => 'STM-'.$investment->id,
        ]);
    }

    public function investmentCertificate(Investment $investment): string
    {
        $investment->loadMissing(['user', 'contract']);

        return $this->render('pdf.investment-certificate', [
            'investment' => $investment,
            'return' => $this->returnLabel($investment),
            'reference' => 'CRT-'.$investment->id,
        ]);
    }

    public function payoutReceipt(Payout $payout): string
    {
        $payout->loadMissing(['investment.user', 'investment.contract']);

        return $this->render('pdf.payout-receipt', [
            'payout' => $payout,
            'reference' => 'RCP-'.$payout->id,
        ]);
    }

    private function returnLabel(Investment $investment): string
    {
        $return = $investment->contract?->expected_return;

        return $return !== null ? rtrim(rtrim((string) $return, '0'), '.').'%' : '—';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $view, array $data): string
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 18,
            'margin_bottom' => 20,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML(view($view, [...$data, 'brand' => $this->brand()])->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Branding pulled from the (cached) Settings service — single source of truth.
     *
     * @return array<string, mixed>
     */
    private function brand(): array
    {
        return [
            'siteName' => setting('general.site_name', 'أساس'),
            'companyName' => setting('general.company_name'),
            'supportEmail' => setting('general.support_email'),
            'supportPhone' => setting('general.support_phone'),
            'taxNumber' => setting('general.tax_number'),
            'issuedAt' => now()->translatedFormat('d F Y'),
        ];
    }
}
