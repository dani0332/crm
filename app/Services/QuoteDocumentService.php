<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeText;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\WatermarkDocTypesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\BorLog;
use App\Models\CarPlanPolicyWording;
use App\Models\DocumentType;
use App\Models\HealthPlanPolicyWording;
use App\Models\InsuranceProvider;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Models\TravelPlanPolicyWording;
use App\Repositories\DocumentTypeRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PhpOffice\PhpWord\IOFactory;
use setasign\Fpdi\Fpdi;

class QuoteDocumentService extends BaseService
{
    protected $client;
    use GenericQueriesAllLobs;

    public function __construct()
    {
        $this->client = new Client;
    }

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
        $enabledLOBs = [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Home, quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::GroupMedical, quoteTypeCode::Business, quoteTypeCode::SAVINGS];

        return in_array($quoteModelType, $enabledLOBs);
    }

    public function getQuoteDocumentsForUpload($quoteTypeId, $options = null)
    {
        LoggerService::info('fn:getQuoteDocumentsForUpload - Start - QuoteDocumentService');

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

        // load quote document with provided detail
        $quote->load(['documents' => function ($q) use ($data) {
            $q->where([
                'doc_name' => $data['doc_name'],
                'doc_uuid' => $data['doc_uuid'],
            ]);
        }]);

        // check for document and delete if found
        if (($document = $quote->documents->first())) {
            $document->delete();
            // LoggerService::info('Document deleted', [
            //     'quote_uuid' => $data['quote_uuid'],
            //     'doc_name' => $data['doc_name']
            // ]);

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
    public function uploadQuoteDocument($fileOrBase64, $data, $quote, $isKyc = false, $isPaymentReceipt = false, $isHomeSAL = false)
    {
        LoggerService::info('fn:uploadQuoteDocument - QuoteDocumentService');

        if (! ($documentType = DocumentType::where('code', $data['document_type_code'])->first())) {
            return response()->json(['error' => 'Invalid document type code provided'], 500);
        }

        $isWaterMarkQualifyDoc = $this->getWatermarkProperty($quote, $documentType);

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
            } elseif ($isHomeSAL) {
                $originalName = $data['pdf_filename'].'.pdf';

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $fileMimeType = 'application/pdf';

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
                $filePathAzure = 'documents/homeSAL/'.$fileNameAzure;
                $uploaded = Storage::disk('azureIM')->put($filePathAzure, $fileOrBase64);
                if (! $uploaded) {
                    return false;
                }
            } else {
                $originalName = sanitizeFileName($fileOrBase64->getClientOriginalName());

                // Generate a unique filename
                $docName = preg_replace('/\s+/', '', $originalName);
                $fileMimeType = $fileOrBase64->getClientMimeType();

                // Set the filename for Azure storage
                $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_original_'.$docName;
                $filePathAzure = $fileOrBase64->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');
            }

            // Generate a unique UUID
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }

            $quoteDocument = $quote->documents()->create([
                'doc_name' => 'original_'.$docName,
                'original_name' => $originalName,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => $fileMimeType,
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentType->text,
                'doc_uuid' => $docUuid,
                'member_detail_id' => $data['member_detail_id'] ?? null,
                'payment_split_type' => $data['split_payment_doc_type'] ?? null,
                'payment_split_id' => $data['payment_split_id'] ?? null,
                'document_category' => $data['document_category'] ?? null,
                'created_by_id' => auth()->id(),
            ]);

            // update the Bor log reference with uploaded document time and status
            ( isset($data['document_category']) && $data['document_category'] !== null ) && $this->updateBorLogReference($data['document_category'], $quoteDocument);

            if (ucfirst(request('quoteType')) == QuoteTypes::TRAVEL->value && $documentType->code == DocumentTypeCode::TRVLPAS) {
                SIBService::createWorkflowEvent(WorkflowTypeEnum::TRAVEL_HAPEX_STOP_EMAIL_REMINDER, $quote, null, $quote);
                LoggerService::info(self::class.'- stopHapexReminder Hapex reminder stopped for Quote UUID: '.$quote->uuid.' | Time - '.now());
            }

            if ($isWaterMarkQualifyDoc && ! $isPaymentReceipt && ! $isKyc && ! $isHomeSAL) {
                WatermarkDocumentsJob::dispatch(
                    $quoteDocument->id,
                    $data['quote_uuid'],
                    $documentType->id
                )->afterCommit();
            }

            return $quoteDocument;
        } catch (\Exception $exception) {
            LoggerService::error('CL: '.get_class().' FN: uploadQuoteDocument  UUID: '.$data['quote_uuid'].' Error Code/Message: '.$exception->getCode().'/'.$exception->getMessage());

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

        if (
            ! isset($record->policy_number) || ! isset($record->policy_issuance_date) || ! isset($record->policy_start_date) ||
            ! isset($record->premium) || ! isset($record->policy_expiry_date) || ! isset($record->plan_id) ||
            $record->advisor_id != auth()->user()->id
        ) {
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
        LoggerService::info('fn:getQuoteDocuments - Start - QuoteDocumentService');

        if ($isSendUpdate) {
            $quote = SendUpdateLog::find($recordId);
        } else {
            $quote = $this->getQuoteObject($quoteType, $recordId);
        }

        if ($quote && $documentTypeCodes) {
            // Return documents filtered by document type codes if provided
            // If watermarked_doc_url is not null then we can send watermarked document in email
            $quoteDocument = $quote->documents()->whereIn('document_type_code', $documentTypeCodes)->with('createdBy:id,name,email')->latest()->get();
            if (ucfirst($quoteType) == quoteTypeCode::Travel) {
                return $quoteDocument->filter(function ($document) {
                    // Exclude documents that contain "Certificate of Insurance" followed by any text or space
                    return ! preg_match('/^Certificate of Insurance\s+\S+/', $document->original_name);
                });
            }

            return $quoteDocument;
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
        LoggerService::info('fn:paymentDocumentTypesOptions - Start - QuoteDocumentService');

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
            QuoteTypeId::CompanyCar => ['CPD', 'CPDR', 'CDPDR'],
            QuoteTypeId::Savings => ['SPD', 'SPDR', 'SDPDR'],
        ];

        return $mapping[$quoteTypeId] ?? [];
    }

    /**
     * Gets handbook documents linked to a policy and formats them as an array with URLs and names.
     *
     * @return array
     */
    public function getHandBookDocuments($quote, $coPaymentIds = null)
    {
        if ($quote->policyWording) {
            // Get policy wording documents for the quote and filter out co-payment documents if provided
            // Filter out policy wording documents that don't have a link
            $policyWording = $quote->policyWording
                ->when($coPaymentIds != null, function ($collection) use ($coPaymentIds) {
                    return $collection->reject(function ($item) use ($coPaymentIds) {
                        return in_array($item->health_plan_co_payment_id, $coPaymentIds);
                    });
                })
                ->filter(function ($item) use ($quote) {
                    if (empty($item->link)) {
                        LoggerService::warning("Policy wording document not found for policy wording ID: {$item->id} Quote Code: {$quote->code} Error Code: 404");

                        return false;
                    }

                    return true;
                });

            $policyWording = $policyWording->map(function ($policyWording) use ($quote) {
                $baseUrl = config('constants.AZURE_IM_STORAGE_URL');
                if (strpos($policyWording->link, $baseUrl) !== 0) {
                    $policyWording->link = rtrim($baseUrl, '/').'/'.ltrim($policyWording->link, '/');
                }
                $link = preg_replace('/[\n\r\t]+/', '', $policyWording->link);
                $extension = pathinfo($link, PATHINFO_EXTENSION); // Get extension first

                return [
                    'url' => preg_replace('/\s+$/m', '', $policyWording->link),
                    'name' => 'InsuranceMarket.ae™ Policy Handbook for Policy Number '.$quote->policy_number.'.'.trim($extension),
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
        LoggerService::info('getAppDownloadLink called for quote code: '.$quote->code);
        if (ucfirst($modelType) == quoteTypeCode::Health) {
            $plan = $quote->plan;

            $healthNetwork = $plan->healthNetwork;
            $code = str_replace(' ', '_', trim($healthNetwork->text)).'_HEALTH_DOC';
            LoggerService::info('Trying health network doc for quote code: '.$quote->code.' with key: '.$code);
            $providerHealthDoc = ApplicationStorage::where('key_name', $code)->first()->value ?? null;

            // If no document found network provider  will check provider document
            if ($providerHealthDoc == null) {
                $code = trim($plan->insuranceProvider->code).'_HEALTH_DOC';
                LoggerService::info('Provider doc for quote code: '.$quote->code.' with key: '.$code);
                $providerHealthDoc = ApplicationStorage::where('key_name', $code)->first()->value ?? null;
            }
            // If these two documents then we send complete url
            if (in_array($code, [ApplicationStorageEnums::BUP_HEALTH_DOC, ApplicationStorageEnums::CIG_HEALTH_DOC])) {
                LoggerService::info('Direct link used for quote code: '.$quote->code.' with key: '.$code);
                $appDownloadLink = $providerHealthDoc;
            } else {
                $baseUrl = config('constants.AZURE_IM_STORAGE_URL');
                LoggerService::info('Base URL prepended for quote code: '.$quote->code.' with key: '.$code);
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
     * @param [type] $uuid
     * @param [type] $documentType
     * @return void
     */
    public function watermarkPdf($file, $docName, $uuid, $documentType)
    {
        if (! file_exists(storage_path('/temp'))) {
            mkdir(storage_path('/temp'), 0775, true);
        }

        $docName = uniqid().'_'.$docName;

        $outputFile = $outputPath = storage_path('temp/'.$docName);
        // Check if file already exists, generate new name if it does
        while (file_exists($outputPath)) {
            $docName = uniqid().'_'.$docName;
            $outputFile = $outputPath = storage_path('temp/'.$docName);
        }

        $azureFilePath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/'.$file;

        $encodedUrl = $this->encodeUrl($azureFilePath);
        $fileContent = file_get_contents($encodedUrl);

        if (! $fileContent) {
            LoggerService::error("Unable to read file azureFilePath: $azureFilePath ");
            throw new \Exception("Unable to read file azureFilePath: $azureFilePath");
        }

        // Save the source file
        $sourceFilePath = storage_path('temp/source_'.$docName);
        file_put_contents($sourceFilePath, $fileContent);

        try {
            // Use QPDF as our primary watermarking approach
            return $this->qpdfWatermark($sourceFilePath, $outputPath, $docName, $uuid, $documentType);
        } catch (\Exception $e) {
            LoggerService::error('Error in watermarkPdf: '.$e->getMessage()." for UUID: $uuid", context: [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            // Incase qpdfWatermark() fails/throw exception. Made sure that we delete the file that it created.
            $watermarkPdf = storage_path('temp/watermark_'.$uuid.'.pdf');
            if (file_exists($watermarkPdf ?? '')) {
                unlink($watermarkPdf);
            }

            // If watermarking fails completely, use the original file without watermark
            if (file_exists($sourceFilePath)) {
                // Copy the original file to the output path
                copy($sourceFilePath, $outputPath);
                LoggerService::info("Using unwatermarked original file due to error for UUID: $uuid");

                return $this->storeWatermarkedMedia($docName, $uuid, $documentType);
            }

            // If we can't even use the original file, re-throw the exception
            throw $e;
        } finally {
            // after everything remove all the temp files from storage/temp
            if (file_exists($sourceFilePath)) {
                unlink($sourceFilePath);
            }

            $qpdfLogPath = storage_path('temp/qpdf_log_'.$uuid.'.txt');
            if (file_exists($qpdfLogPath)) {
                unlink($qpdfLogPath);
            }

            $decryptedTempPath = storage_path('temp/decrypted_'.$docName);
            if (file_exists($decryptedTempPath)) {
                unlink($decryptedTempPath);
            }

            $tempFilePath = storage_path('temp/preprocessed_'.$docName);
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

        }
    }

    private function qpdfWatermark($sourceFilePath, $outputPath, $docName, $uuid, $documentType)
    {
        // Preprocess the PDF with qpdf for FPDI compatibility
        $tempFilePath = storage_path('temp/preprocessed_'.$docName);
        $qpdfLogPath = storage_path('temp/qpdf_log_'.$uuid.'.txt'); // Add log path for qpdf

        $decryptedTempPath = storage_path('temp/decrypted_'.$docName);
        $decryptCommand = 'qpdf --password="" --decrypt '.escapeshellarg($sourceFilePath).' '.
            escapeshellarg($decryptedTempPath).' > '.escapeshellarg($qpdfLogPath).' 2>&1';

        shell_exec($decryptCommand);

        if (! file_exists($decryptedTempPath) || filesize($decryptedTempPath) < 100) {
            $logOutput = file_exists($qpdfLogPath) ? file_get_contents($qpdfLogPath) : 'No log file';

            if (strpos($logOutput, 'invalid password') !== false) {
                /** PDF has password, falling back to original document*/

                // Copy the original file to the output path
                copy($sourceFilePath, $outputPath);

                return $this->storeWatermarkedMedia($docName, $uuid, $documentType);

            } else {
                throw new \Exception("qpdf decryption failed for UUID: $uuid. DocName: $docName, Output: $logOutput");
            }
        }

        // Use qpdf to preprocess the PDF, ensuring compatibility with FPDI
        $qpdfCommand = 'qpdf '.
            '--no-warn '. // Suppress warnings
            '--force-version=1.4 '. // Set PDF version to 1.4 for FPDI
            escapeshellarg($decryptedTempPath).' '.
            escapeshellarg($tempFilePath).' > '.
            escapeshellarg($qpdfLogPath).' 2>&1';

        $output = shell_exec($qpdfCommand);

        if (! file_exists($tempFilePath) || filesize($tempFilePath) < 100) {
            $logOutput = file_exists($qpdfLogPath) ? file_get_contents($qpdfLogPath) : 'No log file';
            LoggerService::error("qpdf preprocessing failed for UUID: $uuid. Output: $logOutput");
            throw new \Exception('qpdf preprocessing failed');
        }

        // region Apply watermark with FPDI
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($tempFilePath);

        $watermarkImagePath = public_path('images/watermark1.png');
        $watermarkImageAA4Path = public_path('images/watermarkAA4.png');

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

            // Add watermark based on orientation
            if ($size['orientation'] === 'P') {
                $pdf->Image(
                    $watermarkImagePath,
                    0, 0, $size['width'], $size['height'],
                    '', '', '', false, 300, '', false, false, 0
                );
            } else {
                $pdf->Image(
                    $watermarkImageAA4Path,
                    0, 0, $size['width'], $size['height'],
                    '', '', '', false, 300, '', false, false, 0
                );
            }

            // Layer the original page content over the watermark
            $pdf->useTemplate($templateId);
        }

        $pdf->Output($outputPath, 'F');
        // endregion

        // Check if the output file was created successfully
        if (! file_exists($outputPath) || filesize($outputPath) < 100) {
            LoggerService::error("FPDI watermarking failed for UUID: $uuid");
            throw new \Exception('FPDI watermarking failed');
        }

        return $this->storeWatermarkedMedia($docName, $uuid, $documentType);
    }

    /**
     * create image watermark function
     *
     * @param [type] $file
     * @param [type] $docName
     * @param [type] $uuid
     * @param [type] $documentType
     * @return void
     */
    public function watermarkImage($file, $docName, $uuid, $documentType)
    {
        if (! file_exists(storage_path('/temp'))) {
            mkdir(storage_path('/temp'), 0775, true);
        }

        $azureFilePath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/'.$file;

        $encodedUrl = $this->encodeUrl($azureFilePath);
        $fileContent = file_get_contents($encodedUrl);

        $manager = new ImageManager(new Driver);

        $image = $manager->read($fileContent);

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

        return $this->storeWatermarkedMedia($docName, $uuid, $documentType);
    }

    /**
     * store watermarked media
     *
     * @param [type] $docName
     * @param [type] $uuid
     * @param [type] $documentType
     * @return void
     */
    public function storeWatermarkedMedia($docName, $uuid, $documentType)
    {
        $watermarkedFile = new \Illuminate\Http\File(storage_path('temp/'.$docName));

        // Set the filename for Azure storage
        $watermarkedFileNameAzure = uniqid().'_'.$uuid.'_'.$docName;
        // upload file to azure
        $filePathAzure = Storage::disk('azureIM')->putFileAs('documents/'.$documentType->folder_path, $watermarkedFile, $watermarkedFileNameAzure);

        // delete temp file
        if (file_exists(storage_path('temp/'.$docName))) {
            unlink(storage_path('temp/'.$docName));
        }

        return [
            'watermarked_doc_name' => $docName,
            'watermarked_doc_url' => $filePathAzure,
        ];
    }

    public function watermarkWordDocs($file, $docName, $uuid, $documentType)
    {
        if (! file_exists(storage_path('/temp'))) {
            mkdir(storage_path('/temp'), 0775, true);
        }

        $azureFilePath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/'.$file;

        $encodedUrl = $this->encodeUrl($azureFilePath);
        $fileContent = file_get_contents($encodedUrl);

        $tempFile = storage_path('temp/'.$docName);
        file_put_contents($tempFile, $fileContent);

        $phpWord = IOFactory::load($tempFile);
        $section = $phpWord->getSection(0);
        // Define the watermark style
        $header = $section->addHeader();
        $header->addWatermark(public_path('images/watermark1.png'));

        // Save the modified document
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return $this->storeWatermarkedMedia($docName, $uuid, $documentType);
    }

    public function isEnableUploadDocument($quoteStatusId)
    {
        if (in_array($quoteStatusId, [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::CancellationPending, QuoteStatusEnum::PolicyCancelled, QuoteStatusEnum::PolicyCancelledReissued])) {
            return false;
        }

        return true;
    }

    public function getDocumentUrl($fileName, $storageDisk = 'azureIM', $expiryTimeInMinutes = 20)
    {
        $expiryTime = now()->addMinutes($expiryTimeInMinutes);

        if (Storage::disk($storageDisk)->exists($fileName)) {
            $encodedFileName = urlencode($fileName);

            return Storage::disk($storageDisk)->temporaryUrl($encodedFileName, $expiryTime);
        } else {
            return null;
        }
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
        $url = $this->getDocumentUrl($fileName, $storageDisk, $expiryTimeInMinutes);

        if ($url) {
            return response()->json(['url' => $url]);
        } else {
            return response()->json(['error' => 'File does not exist on server']);
        }
    }

    /**
     * Check if all required documents are uploaded to enable send policy to customer & book policy button in book policy section
     * Triggering from updateQuoteStatus & bookPolicyPayload
     *
     * @return bool
     */
    public function areDocsUploaded($quoteDocuments, $quoteType, $record)
    {
        $documentTypeCodes = DocumentTypeRepository::sendPolicyDocumentCodes($quoteType, $record);
        $quoteDocumentsCount = collect($quoteDocuments)->whereIn('document_type_code', $documentTypeCodes)->groupBy('document_type_code')->count();

        return $quoteDocumentsCount == count($documentTypeCodes);
    }

    public function getWatermarkProperty($quote, $documentType, $insuranceProviderId = null): bool
    {
        $ips = InsuranceProvider::where('skip_watermark', 1)->select('id')->pluck('id')->toArray();

        if ($insuranceProviderId) {
            $skipWatermark = in_array($insuranceProviderId, $ips);
        } else {
            $insuranceProviderId = $quote->insurance_provider_id;
            if ($insuranceProviderId == null && $quote->plan) {
                $insuranceProviderId = $quote->plan->provider_id;
            }
            if ($insuranceProviderId == null) {
                return false;
            }
            $skipWatermark = in_array($insuranceProviderId, $ips);
        }
        if (! $skipWatermark && in_array($documentType->code, WatermarkDocTypesEnum::asArray())) {
            return true;
        }

        return false;
    }

    /**
     * filter any kind of special encoding on url function
     */
    private function encodeUrl($url)
    {
        // Find the last slash to get the filename
        $lastSlashPos = strrpos($url, '/');

        // Split the URL into the path before the filename and the filename
        $basePath = substr($url, 0, $lastSlashPos + 1);
        $fileName = substr($url, $lastSlashPos + 1);

        // Encode the filename to handle Arabic or special characters
        $encodedFileName = urlencode($fileName);

        // Reconstruct the full URL
        $encodedUrl = $basePath.$encodedFileName;

        return $encodedUrl;
    }

    public function isDocumentExists($quoteType, $quoteId, $documentType)
    {
        $quoteModel = 'App\\Models\\'.ucfirst($quoteType).'Quote';

        return QuoteDocument::where('quote_documentable_type', $quoteModel)
            ->where('quote_documentable_id', $quoteId)
            ->where('document_type_code', $documentType)
            ->exists();
    }

    /**
     * This function use update payment statuses on payments and payment_split table
     *
     * @param [type] $quote
     * @return void
     */
    public function updateQuoteAndPaymentStatusToPaymentPending($quote)
    {
        $quote->quote_status_id = QuoteStatusEnum::PaymentPending;
        $quote->save();
        $payment = $quote->getLastPaymentWithInsurerPaymentLink();
        $payment->payment_status_id = PaymentStatusEnum::PENDING;
        foreach ($payment->paymentSplits as $split) {
            $split->payment_status_id = PaymentStatusEnum::PENDING;
            $split->save();
        }
        $payment->save();
    }

    /**
     * This function bring proof document for all lob's except car and bike
     *
     * @return array
     */
    public function bringProofDocumentForAllLobs()
    {
        $documentTypeCodes = DocumentType::where('text', DocumentTypeText::PAYMENT_PROOF)->whereNotIn('quote_type_id', [QuoteTypeId::Car, QuoteTypeId::Bike])->pluck('code')->toArray();

        return $documentTypeCodes;
    }

    public function checkHandbookDocuments($quoteType)
    {

        if ($quoteType == quoteTypeCode::Car) {
            $carPolicyWordingDocs = CarPlanPolicyWording::get();
            $policyWordingDocuments = $this->formatPolicyWordingDocumentUrls($carPolicyWordingDocs);
        } elseif ($quoteType == quoteTypeCode::Health) {
            $healthPolicyWordingDocs = HealthPlanPolicyWording::get();
            $policyWordingDocuments = $this->formatPolicyWordingDocumentUrls($healthPolicyWordingDocs);
        } elseif ($quoteType == quoteTypeCode::Travel) {
            $travelPolicyWordingDocs = TravelPlanPolicyWording::get();
            $policyWordingDocuments = $this->formatPolicyWordingDocumentUrls($travelPolicyWordingDocs);
        } else {
            LoggerService::error("Invalid quote type: {$quoteType}");

            return;
        }

        $filteredDocuments = $this->filterAttachments($policyWordingDocuments);
        LoggerService::info("Missing policy wording documents for {$quoteType}", extra: [
            'quote_type' => $quoteType,
            'missing_documents' => $filteredDocuments,
            'total_missing' => count($filteredDocuments),
        ]);

    }

    public function formatPolicyWordingDocumentUrls($policyWordingDocs)
    {
        return $policyWordingDocs->map(function ($policyWording) {
            $baseUrl = config('constants.AZURE_IM_STORAGE_URL');
            if (strpos($policyWording->link, $baseUrl) !== 0) {
                $policyWording->link = rtrim($baseUrl, '/').'/'.ltrim($policyWording->link, '/');
            }
            $policyWordingDocumentURL = preg_replace('/\s+$/m', '', $policyWording->link);

            return [
                'id' => $policyWording->id,
                'url' => $policyWordingDocumentURL,
            ];
        });
    }

    public function filterAttachments($documents)
    {
        $attachments = [];
        foreach ($documents as $document) {
            $info = $this->getDocumentInfo($document['url']);
            if ($info['exists']) {
                continue;
            }
            $attachments[] = $document['id'];
        }

        return $attachments;
    }

    private function getDocumentInfo($url)
    {
        try {
            $response = $this->client->head($url);
            if ($response->getStatusCode() == 200) {
                $fileSize = $response->hasHeader('Content-Length') ? $response->getHeader('Content-Length')[0] : 'Unknown';

                return ['exists' => true, 'size' => $fileSize];
            } else {
                return ['exists' => false, 'size' => null];
            }
        } catch (RequestException $e) {
            return ['exists' => false, 'size' => null];
        }
    }

    /**
     * Update the Bor log reference with uploaded document time and status
     *
     * @param string $borReference
     * @return void
     */
    private function updateBorLogReference($borReference, $quoteDocument)
    {
        $borLog = BorLog::where('bor_reference', $borReference)->first();
        if($borLog) {
            $borLog->update([
                'date_uploaded' => now(),
                'document_id' => $quoteDocument->doc_uuid,
                'quote_document_id' => $quoteDocument->id,
                'status' => BorStatusEnum::DOCUMENT_UPLOADED,
            ]);
        }
    }

}
