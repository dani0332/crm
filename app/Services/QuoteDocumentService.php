<?php

namespace App\Services;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Repositories\DocumentTypeRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QuoteDocumentService extends BaseService
{
    use GenericQueriesAllLobs;

    /**
     * get list of active document types can be presented to customer to upload documents.
     *
     * @return mixed
     */
    public function getQuoteDocumentsToReceive($quoteTypeId)
    {
        return DocumentType::where([
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => $quoteTypeId,
        ])
            ->orderBy('sort_order')
            ->get();
    }

    public function isEnabled($quoteModelType)
    {
        $enabledLOBs = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Home, quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::GroupMedical, quoteTypeCode::Business];

        return in_array($quoteModelType, $enabledLOBs);
    }

    public function getQuoteDocumentsForUpload($quoteTypeId, $options = null)
    {
        $query = DocumentType::where(['quote_type_id' => $quoteTypeId, 'is_active' => true]);
        if ($options) {
            $query = $query->whereIn('code', $options);
        }

        return $query->orderBy('sort_order', 'asc')->get();
    }

    public function getSendUpdateDocumentTypes(): array
    {
        return DocumentType::where(['category' => SendUpdateLogStatusEnum::SEND_UPDATE, 'is_active' => true])
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * @param  $data  doc_name, doc_uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteQuoteDocument($quoteType, $data)
    {
        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);

        //load quote document with provided detail
        $quote->load(['documents' => function ($q) use ($data) {
            $q->where([
                'doc_name' => $data['doc_name'],
                'doc_uuid' => $data['doc_uuid'],
            ]);
        }]);

        //check for document and delete if found
        if (($document = $quote->documents->first())) {
            $document->delete();
            //Log::info('CL: '.get_class().' FN: deleteQuoteDocument  UUID: '.$data['quote_uuid'].' Message: document ('.$data['doc_name'].') deleted');

            return response()->json(['message' => 'document deleted successfully']);
        }

        vAbort('Invalid document detail provided.');
    }

    /**
     * upload quote document and store document record in db.
     *
     * @param  $documentTypeCode
     * @param  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadQuoteDocument($fileOrBase64, $data, $quote, $isKyc = false, $isPaymentReceipt = false)
    {
        if (! ($documentType = DocumentType::where('code', $data['document_type_code'])->first())) {
            return response()->json(['error' => 'Invalid document type code provided'], 500);
        }
        try {

            if (data_get($data, 'is_base_64', 0) == 1) {
                $originalName = 'Base 64 file';
                @[$extension, $fileMimeType, $file_data] = getBase64FileInfo($fileOrBase64);

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$data['document_type_code'].'.'.$extension);
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;

                // Set the filename for Azure storage
                $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                Storage::disk('azureIM')->put($filePathAzure, base64_decode($file_data));
            } elseif ($isPaymentReceipt) {
                $originalName = 'Receipt-'.$data['pdf_filename'].'.pdf';

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = 'application/pdf';

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                $uploaded = Storage::disk('azureIM')->put($filePathAzure, $fileOrBase64);
                if (! $uploaded) {
                    return false;
                }
            } elseif ($isKyc) {
                $originalName = 'SystemGeneratedKycDocument.pdf';

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = $documentType->accepted_files;

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                $uploaded = Storage::disk('azureIM')->put($filePathAzure, $fileOrBase64);
                if (! $uploaded) {
                    return false;
                }
            } else {
                $originalName = sanitizeFileName($fileOrBase64->getClientOriginalName());

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = $fileOrBase64->getClientMimeType();

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = $fileOrBase64->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');
            }

            // Generate a unique UUID
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }

            return $quote->documents()->create([
                'doc_name' => $docName,
                'original_name' => $originalName,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => $fileMimeType,
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentType->text,
                'doc_uuid' => $docUuid,
                'member_detail_id' => $data['member_detail_id'] ?? null,
                'payment_split_type' => $data['split_payment_doc_type'] ?? null,
                'payment_split_id' => $data['payment_split_id'] ?? null,
                'created_by_id' => auth()->id(),
            ]);
        } catch (\Exception $exception) {
            Log::info('CL: '.get_class().' FN: uploadQuoteDocument  UUID: '.$data['quote_uuid'].' Error Code/Message: '.$exception->getCode().'/'.$exception->getMessage());

            return response()->json(['error' => 'Document upload failed, please try again'], 500);
        }
    }

    public function getQuoteDocumentUrl($id)
    {
        $quoteDocument = QuoteDocument::where('doc_uuid', $id)->first();

        if (! $quoteDocument) {
            abort(404);
        }

        return (object) ['doc_url' => $quoteDocument->doc_url, 'doc_mime_type' => $quoteDocument->doc_mime_type];
    }

    public function showSendPolicyButton($record, $quoteDocuments, $quoteTypeId)
    {
        if (! $record) {
            return 0;
        }

        if (! auth()->user()->hasRole(RolesEnum::BetaUser)) {
            return false;
        }

        if (! isset($record->policy_number) || ! isset($record->policy_issuance_date) || ! isset($record->policy_start_date) ||
            ! isset($record->premium) || ! isset($record->renewal_expiry_date) || ! isset($record->plan_id) ||
            $record->advisor_id != auth()->user()->id) {
            return 0;
        }

        $documentUploadTypes = $this->getQuoteDocumentsForUpload($quoteTypeId);
        if (! $documentUploadTypes) {
            return 0;
        }
        $displaySendPolicyButton = 1;
        foreach ($documentUploadTypes->where('is_required', 1) as $documentUploadType) {
            if ($quoteDocuments->where('document_type_code', $documentUploadType->code)->count() == 0) {
                $displaySendPolicyButton = 0;
                break;
            }
        }

        return $displaySendPolicyButton;
    }

    public function getQuoteDocuments($quoteType, $recordId, $documentTypeCodes = null)
    {
        $quote = $this->getQuoteObject($quoteType, $recordId);

        if ($quote && $documentTypeCodes) {
            return $quote->documents()->whereIn('document_type_code', $documentTypeCodes)->with('createdBy:id,name,email')->latest()->get();
        }

        return $quote ? $quote->documents()->with('createdBy:id,name,email')->latest()->get() : [];
    }

    public function getDocumentTypes($quoteTypeId, $businessTypeOfInsurance = null, $businessTypeOfCustomer = null, $quoteType = null)
    {

        $businessInsurerName = false;
        $documentTypes = DocumentType::active()
            ->whereNotIn('category', ['SEND_UPDATE', 'ENDORSEMENT_DOCUMENTS'])
            ->byQuoteTypeId($quoteTypeId)
            ->when($businessTypeOfInsurance, function ($query) use ($businessTypeOfInsurance) {
                return $query->byBusinessTypeOfInsurance($businessTypeOfInsurance);
            })
            ->when($businessTypeOfCustomer, function ($query) use ($businessTypeOfCustomer, $businessTypeOfInsurance) {
                $businessInsurerName = DocumentTypeRepository::businessInsurerName($businessTypeOfInsurance);
                return $query->byBusinessTypeOfCustomer($businessTypeOfCustomer, $businessInsurerName);
            })->sortDocumentType()->get();

        if ($quoteTypeId == QuoteTypeId::Business) {
            $businessDocumetTypes = [];
            if ($quoteType == quoteTypeCode::GroupMedical) {
                $businessDocumetTypes = [DocumentTypeCode::GMQPD, DocumentTypeCode::GMQPDR, DocumentTypeCode::GMQDPDR, DocumentTypeCode::PPR];
            } elseif ($quoteType == quoteTypeCode::CORPLINE) {
                $businessDocumetTypes = [DocumentTypeCode::CLPD, DocumentTypeCode::CLPDR, DocumentTypeCode::CLDPDR, DocumentTypeCode::PPR];
            }
            $businessDocumentTypeCodes = DocumentType::active()->where('quote_type_id', QuoteTypeId::Business)->whereIn('code', $businessDocumetTypes)->get();
        }

        $documentTypesByCategory = $documentTypes->groupBy('category');
        $orderedDocumentTypesByCategory = collect();
        if ($documentTypesByCategory->has('QUOTE') || $quoteTypeId == QuoteTypeId::Business) {
            if ($quoteTypeId == QuoteTypeId::Business) {
                $quoteDocumentTypes = $documentTypesByCategory->get('QUOTE');
                if ($quoteDocumentTypes === null) {
                    $quoteDocumentTypes = collect();
                }
                $quoteDocumentTypes = $quoteDocumentTypes->concat($businessDocumentTypeCodes);
                $orderedDocumentTypesByCategory->put('QUOTE', $quoteDocumentTypes);
            } else {
                $orderedDocumentTypesByCategory->put('QUOTE', $documentTypesByCategory->get('QUOTE'));
            }
        }
        if ($documentTypesByCategory->has('MEMBER')) {
            $orderedDocumentTypesByCategory->put('MEMBER', $documentTypesByCategory->get('MEMBER'));
        }
        if ($documentTypesByCategory->has('ISSUING_DOCUMENTS')) {
            $orderedDocumentTypesByCategory->put('ISSUING_DOCUMENTS', $documentTypesByCategory->get('ISSUING_DOCUMENTS'));
        }

        return [$orderedDocumentTypesByCategory, $documentTypes, $businessDocumentTypeCodes ?? []];
    }

    public function getQuoteDocumentsForSendUpdates($sendUpdateLogId)
    {
        $sendUpdateLog = SendUpdateLog::where('id', $sendUpdateLogId)->firstOrFail();

        return $sendUpdateLog->documents()->with('createdBy:id,name,email')->latest()->get();
    }

    public function paymentDocumentTypesOptions($quoteTypeId): array
    {
        $mapping = [
            QuoteTypeId::Car => ['CPD', 'CPDR', 'CDPDR'],
            QuoteTypeId::Health => ['HPD', 'HPDR', 'HDPDR'],
            QuoteTypeId::Travel => ['TPD', 'TPDR', 'TDPDR'],
            QuoteTypeId::Life => ['LPD', 'LPDR', 'LDPDR'],
            QuoteTypeId::Home => ['HOMPD', 'HOMPDR', 'HOMDPDR'],
            QuoteTypeId::Pet => ['PPD', 'PPDR', 'PDPDR'],
            QuoteTypeId::Bike => ['BPD', 'BPDR', 'BDPDR'],
            QuoteTypeId::Cycle => ['CYCPD', 'CYCPDR', 'CYCDPDR'],
            QuoteTypeId::Yacht => ['YPD', 'YPDR', 'YDPDR'],
        ];

        return $mapping[$quoteTypeId] ?? [];
    }
}
