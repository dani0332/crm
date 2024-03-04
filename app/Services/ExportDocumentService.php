<?php

namespace App\Services;

use App\Interfaces\ExportDocumentInterface;
use App\Traits\GenericQueriesAllLobs;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportDocumentService extends BaseService implements ExportDocumentInterface
{
    use GenericQueriesAllLobs;
    public function exportProformaPaymentRequest($quoteType, $quote)
    {
        $quote = $this->getQuote($quoteType, $quote);
        if (isset($response['error'])) {
            return $quote;
        }
        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.proforma-invoice', compact('quote'));

        $pdfName = 'InsuranceMarket.ae™ Motor Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }
    private function getQuote($quoteType, $quote)
    {
        $repository = $this->getRepositoryObject($quoteType);
        if (! $repository) {
            return ['error' => 'Repository not found'];
        }

        return $repository::getBy('uuid', $quote);
    }
}
