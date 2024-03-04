<?php

namespace App\Services;

use App\Enums\DocumentTypeEnum;
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
        $proformaPaymentRequest = $quote->payments()->where('payment_methods_code', \App\Enums\PaymentMethodsEnum::ProformaPaymentRequest)->first();
        if (! $proformaPaymentRequest) {
            return ['error' => 'Proforma Payment Request not found'];
        }
        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.proforma-invoice', compact('quote'));

        $pdfName = 'InsuranceMarket.ae™ Proforma Payment Request for '.$quote->first_name.' '.$quote->last_name.'-'.$proformaPaymentRequest->code.'.pdf';

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

    public function saveProformaPaymentRequestToDocuments($quoteType, $quoteUUID, $pdf, $fileName)
    {
        $quote = $this->getQuote($quoteType, $quoteUUID);

        $path = 'app/public/'.$fileName;
        $pdf->save(storage_path($path));

        $quote->documents()->create([
            'original_name' => $fileName,
            'doc_name' => $fileName,
            'doc_url' => $path,
            'document_type_text' => DocumentTypeEnum::ProformaPaymentRequest,
        ]);
    }

}
