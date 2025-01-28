<?php

namespace App\Services;

use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\PaymentMethodsEnum;
use App\Http\Resources\ProformaPaymentRequestResource;
use App\Interfaces\ExportDocumentInterface;
use App\Models\Payment;
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

    public function createProformaPaymentRequestPdf($quoteType, $quoteUuid, $request)
    {
        /*
        INFO: Getting Quote by LOB Model because payments are linked with Quotes using polymorphic relationship
        and later on if we have to change it than have to make it at single place i.e logic for newUI() method in all LOB Models
        */
        $quote = $this->getQuoteObject($quoteType, $quoteUuid);
        if (! $quote) {
            return ['error' => 'Quote  not found'];
        }
        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($quoteType));
        /* filter_var is used to convert boolean values sent from frontend */
        $isRequestFromSendUpdateLogPage = $request->has('isSendUpdateLogRoute') && filter_var($request->isSendUpdateLogRoute, FILTER_VALIDATE_BOOLEAN);

        // check if request is generated from Send Update Log Page or from LOB page
        if ($isRequestFromSendUpdateLogPage) {
            $proformaPaymentRequest = Payment::where(['code' => $request->paymentCode, 'payment_methods_code' => PaymentMethodsEnum::ProformaPaymentRequest])->first();
            $sendUpdateLog = $proformaPaymentRequest->sendUpdateLog;
            $proformaPaymentRequestVersion = $sendUpdateLog->documents()->where(['document_type_text' => DocumentTypeEnum::ProformaPaymentRequest])->count();
            $docmentableTypeEntry = $sendUpdateLog;
        } else {
            $proformaPaymentRequest = $quote->payments()->where('payment_methods_code', PaymentMethodsEnum::ProformaPaymentRequest)->first();
            $proformaPaymentRequestVersion = $quote->documents()->where(['document_type_text' => DocumentTypeEnum::ProformaPaymentRequest])->count();
            $docmentableTypeEntry = $quote;
        }

        if (! $proformaPaymentRequest) {
            return ['error' => 'Proforma Payment Request not found'];
        }

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.proforma-invoice', compact('quote', 'proformaPaymentRequest', 'isRequestFromSendUpdateLogPage', 'quoteTypeId'));

        $pdfName = 'InsuranceMarket.ae™ Proforma Payment Request for '.$quote->first_name.' '.$quote->last_name.'-'.$proformaPaymentRequest->code.'('.($proformaPaymentRequestVersion + 1).')'.'.pdf';

        return $this->saveProformaPaymentRequestToDocuments($docmentableTypeEntry, $pdf, $pdfName, $quoteType);
    }

    private function saveProformaPaymentRequestToDocuments($docmentableTypeEntry, $pdf, $originalName, $quoteType)
    {
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = 'application/pdf';

        // upload file to azure
        $fileNameAzure = uniqid().'_'.$docmentableTypeEntry->uuid.'_'.$docName;
        $filePathAzure = 'documents/'.ucwords($quoteType).'/'.$fileNameAzure;
        $azureDisk = Storage::disk('azureIM');
        $azureDisk->put($filePathAzure, $pdf->output());

        $document = $docmentableTypeEntry->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $fileMimeType,
            'document_type_code' => DocumentTypeCode::PPR,
            'document_type_text' => DocumentTypeEnum::ProformaPaymentRequest,
            'doc_uuid' => $this->helperService->generateUUID(),
            'created_by_id' => auth()->id(),
        ]);

        return new ProformaPaymentRequestResource($document);
    }

}
