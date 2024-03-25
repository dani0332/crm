<?php

namespace App\Services;

use App\Enums\DocumentTypeEnum;
use App\Enums\PaymentMethodsEnum;
use App\Http\Resources\ProformaPaymentRequestResource;
use App\Interfaces\ExportDocumentInterface;
use App\Traits\GenericQueriesAllLobs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExportDocumentService extends BaseService implements ExportDocumentInterface
{
    use GenericQueriesAllLobs;

    protected $helperService;

    public function __construct(HelperService $helperService)
    {
        $this->helperService = $helperService;
    }

    public function createProformaPaymentRequestPdf($quoteType, $quote)
    {
        $quote = $this->getQuote($quoteType, $quote);
        if (isset($response['error'])) {
            return $quote;
        }
        $proformaPaymentRequest = $quote->payments()->where('payment_methods_code', PaymentMethodsEnum::ProformaPaymentRequest)->first();
        $proformaPaymentRequestVersion = $quote->documents()->where(['document_type_text' => DocumentTypeEnum::ProformaPaymentRequest])->count();

        if (! $proformaPaymentRequest) {
            return ['error' => 'Proforma Payment Request not found'];
        }
        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.proforma-invoice', compact('quote'));

        $pdfName = 'InsuranceMarket.ae™ Proforma Payment Request for '.$quote->first_name.' '.$quote->last_name.'-'.$proformaPaymentRequest->code.($proformaPaymentRequestVersion > 0 ? '('.($proformaPaymentRequestVersion + 1).')' : '').'.pdf';

        return $this->saveProformaPaymentRequestToDocuments($quote, $pdf, $pdfName);
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

        $document = $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $fileMimeType,
            'document_type_text' => DocumentTypeEnum::ProformaPaymentRequest,
            'doc_uuid' => $this->helperService->generateUUID(),
            'created_by_id' => auth()->id(),
        ]);

        return new ProformaPaymentRequestResource($document);
    }

}
