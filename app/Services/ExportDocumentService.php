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

    public function createProformaPaymentRequestPdf($quoteType, $quoteUuid)
    {
        // INFO: Getting Quote by LOB Model because payments are linked with Quotes using polymorphic relationship and later on if we have to change it than have to make it at single place i.e logic for newUI() method in all LOB Models
        $quote = $this->getQuoteObject($quoteType, $quoteUuid);
        if (! $quote) {
            return ['error' => 'Quote  not found'];
        }
        $proformaPaymentRequest = $quote->payments()->where('payment_methods_code', PaymentMethodsEnum::ProformaPaymentRequest)->first();
        $proformaPaymentRequestVersion = $quote->documents()->where(['document_type_text' => DocumentTypeEnum::ProformaPaymentRequest])->count();

        if (! $proformaPaymentRequest) {
            return ['error' => 'Proforma Payment Request not found'];
        }
        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.proforma-invoice', compact('quote'));

        $pdfName = 'InsuranceMarket.ae™ Proforma Payment Request for '.$quote->first_name.' '.$quote->last_name.'-'.$proformaPaymentRequest->code.'('.($proformaPaymentRequestVersion + 1).')'.'.pdf';

        return $this->saveProformaPaymentRequestToDocuments($quote, $pdf, $pdfName, $quoteType);
    }

    private function saveProformaPaymentRequestToDocuments($quote, $pdf, $originalName, $quoteType)
    {
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = 'application/pdf';

        //upload file to azure
        $fileNameAzure = uniqid().'_'.$quote->uuid.'_'.$docName;
        $filePathAzure = 'documents/'.ucwords($quoteType).'/'.$fileNameAzure;
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
