<?php

namespace App\Services;

use App\Enums\DocumentTypeEnum;
use App\Interfaces\ExportDocumentInterface;
use App\Models\QuoteDocument;
use App\Traits\GenericQueriesAllLobs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
        $proformaPaymentRequestVersion = $quote->documents()->where(['document_type_text' => DocumentTypeEnum::ProformaPaymentRequest])->count();

        if (! $proformaPaymentRequest) {
            return ['error' => 'Proforma Payment Request not found'];
        }
        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.proforma-invoice', compact('quote'));

        $pdfName = 'InsuranceMarket.ae™ Proforma Payment Request for '.$quote->first_name.' '.$quote->last_name.'-'.$proformaPaymentRequest->code.($proformaPaymentRequestVersion > 0 ? '('.($proformaPaymentRequestVersion + 1).')' : '').'.pdf';

        $this->saveProformaPaymentRequestToDocuments($quote, $pdf, $pdfName);

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

    private function saveProformaPaymentRequestToDocuments($quote, $pdf, $originalName)
    {
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = 'application/pdf';

        //upload file to azure
        $fileNameAzure = uniqid().'_'.$quote->uuid.'_'.$docName;
        $filePathAzure = 'documents/'.$quote->quoteType->code.'/'.$fileNameAzure;
        $azureDisk = Storage::disk('azureIM');
        $azureDisk->put($filePathAzure, $pdf->output());

        $docUuid = uniqid();
        while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
            $docUuid = uniqid().rand(1, 100);
        }

        return $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $fileMimeType,
            'document_type_text' => DocumentTypeEnum::ProformaPaymentRequest,
            'doc_uuid' => $docUuid,
            'created_by_id' => auth()->id(),
        ]);
    }

}
