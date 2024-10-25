<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\WatermarkDocTypesEnum;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Repositories\DocumentTypeRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PhpOffice\PhpWord\IOFactory;
use setasign\Fpdi\Fpdi;

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
        $sendUpdateDocumentTypes = DocumentType::active()
            ->whereIn('category', [SendUpdateLogStatusEnum::SEND_UPDATE, DocumentTypeCategory::QUOTE_AND_ENDORSEMENT])
            ->orderBy('sort_order')
            ->get();

        // Document types for send updates are grouped by category.
        $documentTypesByCategory = $sendUpdateDocumentTypes->groupBy('category');
        $groupedDocumentTypesByCategory = $documentTypesByCategory->map(function ($documentType) {
            return $documentType->toArray();
        });

        foreach ($documentTypesByCategory as $category => $documentTypeByCategory) {
            $groupedDocumentTypesByCategory->put($category, $documentTypesByCategory->get($category)->toArray());
        }

        return $groupedDocumentTypesByCategory->toArray();
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
    public function uploadQuoteDocument($fileOrBase64, $data, $quote, $isKyc = false, $isPaymentReceipt = false, $tempKycFile = null)
    {
        if (! ($documentType = DocumentType::where('code', $data['document_type_code'])->first())) {
            return response()->json(['error' => 'Invalid document type code provided'], 500);
        }

        $isWaterMarkQualifyDoc = in_array($documentType->code, WatermarkDocTypesEnum::asArray());

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
                $fileOrBase64 = $tempKycFile;
            } elseif ($isKyc) {
                if (isset($data['pdf_name'])) {
                    $originalName = $data['pdf_name'];
                } else {
                    $originalName = 'SystemGeneratedKycDocument.pdf';
                }

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
                $fileOrBase64 = $tempKycFile;
            } else {
                $originalName = sanitizeFileName($fileOrBase64->getClientOriginalName());

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = $fileOrBase64->getClientMimeType();

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_original_'.$docName;
                $filePathAzure = $fileOrBase64->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');
            }

            // watermark only for pdf files
            if (($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf') && $isWaterMarkQualifyDoc && ! $isPaymentReceipt && ! $isKyc) {
                $watermarkData = $this->watermarkPdf($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType, $isKyc, $isPaymentReceipt);
            } elseif (($fileMimeType == 'image/jpeg' || $fileMimeType == 'image/png' || $fileMimeType == 'image/jpg') && $isWaterMarkQualifyDoc) {
                $watermarkData = $this->watermarkImage($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType);
            } elseif (($fileMimeType == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $fileMimeType == 'application/msword') && $isWaterMarkQualifyDoc) {
                $watermarkData = $this->watermarkWordDocs($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType);
            }

            // Generate a unique UUID
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }

            return $quote->documents()->create([
                'doc_name' => 'original_'.$docName,
                'watermarked_doc_name' => $watermarkData['watermarked_doc_name'] ?? null,
                'original_name' => $originalName,
                'doc_url' => $filePathAzure,
                'watermarked_doc_url' => $watermarkData['watermarked_doc_url'] ?? null,
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
            ! isset($record->premium) || ! isset($record->policy_expiry_date) || ! isset($record->plan_id) ||
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

    /**
     * This method fetches all or a subset of documents linked to a specific quote, based on the provided document type codes.
     *
     * @return Collection
     */
    public function getQuoteDocuments($quoteType, $recordId, $documentTypeCodes = null, $isSendUpdate = false)
    {
        if ($isSendUpdate) {
            $quote = SendUpdateLog::find($recordId);
        } else {
            $quote = $this->getQuoteObject($quoteType, $recordId);
        }

        if ($quote && $documentTypeCodes) {
            // Return documents filtered by document type codes if provided
            return $quote->documents()->whereIn('document_type_code', $documentTypeCodes)->with('createdBy:id,name,email')->latest()->get();
        }

        // Return all documents associated with the quote if no specific document type codes are provided
        return $quote ? $quote->documents()->with('createdBy:id,name,email')->latest()->get() : [];
    }

    /**
     * Retrieves all document types associated with a specific quote, fetches active document types & excluding certain categories
     * This method fetches active document types, excluding certain categories, and can further filter them based on
     * It also organizes documents by category and get payment-related documents used for all LOB's
     *
     * @return array
     */
    public function getDocumentTypes($quoteTypeId, $businessTypeOfInsurance = null, $businessTypeOfCustomer = null, $quoteType = null)
    {
        // Fetch active document types, excluding 'SEND_UPDATE' and 'ENDORSEMENT_DOCUMENTS' categories, and filter by quote type ID.
        $documentTypes = DocumentType::active()
            ->whereNotIn('category', ['SEND_UPDATE', 'ENDORSEMENT_DOCUMENTS'])
            ->byQuoteTypeId($quoteTypeId)
            // Apply filters for business type of insurance & business type of customer if provided.
            ->when($businessTypeOfInsurance, function ($query) use ($businessTypeOfInsurance) {
                return $query->byBusinessTypeOfInsurance($businessTypeOfInsurance);
            })
            ->when($businessTypeOfCustomer, function ($query) use ($businessTypeOfCustomer, $businessTypeOfInsurance) {
                $businessInsurerName = DocumentTypeRepository::businessInsurerName($businessTypeOfInsurance);

                return $query->byBusinessTypeOfCustomer($businessTypeOfCustomer, $businessInsurerName);
            })
            ->sortDocumentType()->get();

        // Handle documents for quote types like CORPLINE and GroupMedical.
        if ($quoteTypeId == QuoteTypeId::Business) {
            if ($quoteType == quoteTypeCode::CORPLINE) {
                $quoteTypeId = QuoteTypeId::Corpline;
            }
            $businessDocumetTypes = [];
            if ($quoteType == quoteTypeCode::GroupMedical) {
                $businessDocumetTypes = [DocumentTypeCode::GMQPD, DocumentTypeCode::GMQPDR, DocumentTypeCode::GMQDPDR, DocumentTypeCode::PPR];
            } elseif ($quoteType == quoteTypeCode::CORPLINE) {
                $businessDocumetTypes = [DocumentTypeCode::CLPD, DocumentTypeCode::CLPDR, DocumentTypeCode::CLDPDR, DocumentTypeCode::PPR];
            }
            $businessDocumetTypes[] = DocumentTypeCode::AUDIT;
            // Fetch additional business document types based on the specific quote type.
            $businessDocumetTypes = DocumentType::active()->where('quote_type_id', QuoteTypeId::Business)->whereIn('code', $businessDocumetTypes)->sortDocumentType()->get();
            $documentTypes = $documentTypes->merge($businessDocumetTypes);
        }

        // Filter for payment-related document types.
        $paymentDocumentCodes = $this->paymentDocumentTypesOptions($quoteTypeId);
        $paymentDocuments = $documentTypes->filter(function ($type) use ($paymentDocumentCodes) {
            return in_array($type->code, $paymentDocumentCodes);
        })->values()->all();

        // Organize document types by category.
        $documentTypesByCategory = $documentTypes->groupBy('category');
        $orderedDocumentTypesByCategory = collect();
        if ($documentTypesByCategory->has('QUOTE')) {
            $orderedDocumentTypesByCategory->put('QUOTE', $documentTypesByCategory->get('QUOTE'));
        }
        if ($documentTypesByCategory->has('MEMBER')) {
            $orderedDocumentTypesByCategory->put('MEMBER', $documentTypesByCategory->get('MEMBER'));
        }
        if ($documentTypesByCategory->has('ISSUING_DOCUMENTS')) {
            $orderedDocumentTypesByCategory->put('ISSUING_DOCUMENTS', $documentTypesByCategory->get('ISSUING_DOCUMENTS'));
        }

        // Return the organized document types by category and the payment-related documents.
        return [$orderedDocumentTypesByCategory, $paymentDocuments];
    }

    public function getQuoteDocumentsForSendUpdates($sendUpdateLogId)
    {
        $sendUpdateLog = SendUpdateLog::where('id', $sendUpdateLogId)->firstOrFail();

        return $sendUpdateLog->documents()->with('createdBy:id,name,email')->latest()->get();
    }

    /**
     * Returns an array of document type codes for payment documents based on the quote type ID.
     */
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
            QuoteTypeId::Business => ['GMQPD', 'GMQPDR', 'GMQDPDR'],
            QuoteTypeId::Corpline => ['CLPD', 'CLPDR', 'CLDPDR'],
        ];

        return $mapping[$quoteTypeId] ?? [];
    }

    /**
     * Gets handbook documents linked to a policy and formats them as an array with URLs and names.
     *
     * @return array
     */
    public function getHandBookDocuments($quote)
    {
        if ($quote->policyWording) {
            $policyWording = $quote->policyWording->map(function ($policyWording) use ($quote) {
                $baseUrl = config('constants.AZURE_IM_STORAGE_URL');
                if (strpos($policyWording->link, $baseUrl) !== 0) {
                    $policyWording->link = rtrim($baseUrl, '/').'/'.ltrim($policyWording->link, '/');
                }

                return [
                    'url' => preg_replace('/\s+$/m', '', $policyWording->link),
                    'name' => 'InsuranceMarket.ae™ Policy Handbook for Policy Number '.$quote->policy_number.'.'.pathinfo($policyWording->link, PATHINFO_EXTENSION),
                ];
            });

            return $policyWording->toArray();
        }

        return [];
    }

    /**
     * Get app download linked for Health LOB
     *
     * @return array
     */
    public function getAppDownloadLink($modelType, $quote)
    {
        $appDownloadLink = '';
        if (ucfirst($modelType) == quoteTypeCode::Health) {
            $plan = $quote->plan;
            $code = $plan->insuranceProvider->code.'_HEALTH_DOC';
            $providerHealthDoc = ApplicationStorage::where('key_name', $code)->first()->value ?? null;
            // If no document found against provider  will check health network document
            if ($providerHealthDoc == null) {
                $healthNetwork = $plan->healthNetwork;
                $code = str_replace(' ', '_', $healthNetwork->text).'_HEALTH_DOC';
                $providerHealthDoc = ApplicationStorage::where('key_name', $code)->first()->value ?? null;
            }
            // If these two documents then we send complete url
            if (in_array($code, [ApplicationStorageEnums::BUP_HEALTH_DOC, ApplicationStorageEnums::CIG_HEALTH_DOC])) {
                $appDownloadLink = $providerHealthDoc;
            } else {
                $baseUrl = config('constants.AZURE_IM_STORAGE_URL');
                $appDownloadLink = $baseUrl.$providerHealthDoc;
            }
        }

        return $appDownloadLink;
    }

    /**
     * create pdf watermark function
     *
     * @param [type] $file
     * @param [type] $docName
     * @param [type] $data
     * @param [type] $quote
     * @param [type] $documentType
     * @param [type] $originalName
     * @param [type] $fileMimeType
     * @return void
     */
    public function watermarkPdf($file, $docName, $data, $quote, $documentType, $originalName, $fileMimeType, $isKyc = false, $isPaymentReceipt = false)
    {
        if (! file_exists(storage_path('/temp'))) {
            mkdir(storage_path('/temp'), 0775, true);
        }

        if ($isKyc || $isPaymentReceipt) {
            $file->move(storage_path('temp'), $docName);
            $filePath = 'temp/'.$docName;
            $outputPath = storage_path('temp/'.$docName);
        } else {
            $filePath = $file->storeAs('temp', $docName);
            $outputPath = storage_path('temp/'.$docName);
        }

        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile(storage_path('app/'.$filePath));

        $watermarkImagePath = public_path('images/watermark1.png');
        $watermarkImageAA4Path = public_path('images/watermarkAA4.png');

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            Log::info('Page size: '.json_encode($size));

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            // Add watermark
            if ($size['orientation'] === 'P') {
                $pdf->Image($watermarkImagePath, 0, 0, $size['width'], $size['height'], '', '', '', false, 300, '', false, false, 0);
            } else {
                $pdf->Image($watermarkImageAA4Path, 0, 0, $size['width'], $size['height'], '', '', '', false, 300, '', false, false, 0);
            }

            $pdf->useTemplate($templateId);
        }

        $pdf->Output($outputPath, 'F');

        return $this->storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType);
    }

    /**
     * create image watermark function
     *
     * @param [type] $file
     * @param [type] $docName
     * @param [type] $data
     * @param [type] $quote
     * @param [type] $documentType
     * @param [type] $originalName
     * @param [type] $fileMimeType
     * @return void
     */
    public function watermarkImage($file, $docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        if (! file_exists(storage_path('/temp'))) {
            mkdir(storage_path('/temp'), 0775, true);
        }

        $manager = new ImageManager(new Driver);

        $image = $manager->read($file);

        // Get image dimensions
        $imageWidth = $image->width();
        $imageHeight = $image->height();

        if ($imageWidth > 1000) {
            $watermarkPath = public_path('images/watermark2AA4.png');
        } else {
            $watermarkPath = public_path('images/watermark2.png');
        }

        // resize the watermark based on the image size
        $watermark = $manager->read($watermarkPath)->resize(
            intval($imageWidth),
            intval($imageHeight),
            function ($constraint) {
                $constraint->aspectRatio();
            }
        );

        $image->place(
            $watermark,
            'center',
            10,
            10,
            15
        );

        $image->save(storage_path('temp/'.$docName));

        return $this->storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType);
    }

    /**
     * store watermarked media
     *
     * @param [type] $docName
     * @param [type] $data
     * @param [type] $quote
     * @param [type] $documentType
     * @param [type] $originalName
     * @param [type] $fileMimeType
     * @return void
     */
    public function storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        $watermarkedFile = new \Illuminate\Http\File(storage_path('temp/'.$docName));

        // Set the filename for Azure storage
        $watermarkedFileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
        // upload file to azure
        $filePathAzure = Storage::disk('azureIM')->putFileAs('documents/'.$documentType->folder_path, $watermarkedFile, $watermarkedFileNameAzure);

        // Generate a unique UUID
        $docUuid = uniqid();
        while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
            $docUuid = uniqid().rand(1, 100);
        }

        // delete temp file
        unlink(storage_path('temp/'.$docName));

        return [
            'watermarked_doc_name' => $docName,
            'watermarked_doc_url' => $filePathAzure,
        ];
    }

    public function watermarkWordDocs($fileOrBase64, $docName, $data, $quote, $documentType, $originalName, $fileMimeType)
    {
        if (! file_exists(storage_path('/temp'))) {
            mkdir(storage_path('/temp'), 0775, true);
        }

        $tempFile = $fileOrBase64->move(storage_path('/temp'), $docName)->getRealPath();

        $phpWord = IOFactory::load($tempFile);
        $section = $phpWord->getSection(0);
        // Define the watermark style
        $header = $section->addHeader();
        $header->addWatermark(public_path('images/watermark1.png'));

        // Save the modified document
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return $this->storeWatermarkedMedia($docName, $data, $quote, $documentType, $originalName, $fileMimeType);
    }

    public function isEnableUploadDocument($quoteStatusId)
    {
        if (in_array($quoteStatusId, [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::CancellationPending, QuoteStatusEnum::PolicyCancelled, QuoteStatusEnum::PolicyCancelledReissued])) {
            return false;
        }

        return true;
    }

    /**
     * Generate a temporary URL for a document stored in a specified storage disk.
     *
     * @param  string  $fileName  The name of the file for which to generate the temporary URL.
     * @param  string  $storageDisk  The storage disk where the file is located. Default is 'azureIM'.
     * @param  int  $expiryTimeInMinutes  The expiry time for the temporary URL in minutes. Default is 20 minutes.
     * @return \Illuminate\Http\JsonResponse JSON response containing the temporary URL or an error message.
     */
    public function getDocumentTempURL($fileName, $storageDisk = 'azureIM', $expiryTimeInMinutes = 20)
    {
        // Calculate the expiry time for the temporary URL
        $expiryTime = now()->addMinutes($expiryTimeInMinutes);

        // Check if the file exists in the specified storage disk
        if (Storage::disk($storageDisk)->exists($fileName)) {
            // Generate the temporary URL
            $encodedFileName = urlencode($fileName);
            $temporaryUrl = Storage::disk($storageDisk)->temporaryUrl($encodedFileName, $expiryTime);

            // Return the temporary URL if generated
            return response()->json(['url' => $temporaryUrl]);
        } else {
            // Return an error message if the file does not exist
            return response()->json(['error' => 'File does not exist on server']);
        }
    }
}
