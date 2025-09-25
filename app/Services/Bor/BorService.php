<?php

namespace App\Services\Bor;

use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\BorLog;
use App\Models\DocumentType;
use App\Models\InsuranceProviderContact;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\DB;

class BorService
{
    use GenericQueriesAllLobs;

    protected $borEmailService;
    protected $borPdfService;

    public function __construct(BorEmailService $borEmailService, BorPdfService $borPdfService)
    {
        $this->borEmailService = $borEmailService;
        $this->borPdfService = $borPdfService;
    }

    /**
     * Get BOR logs for a lead with embedded document data
     *
     * @param array $data
     * @return array
     */
    public function getBorLogs(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['leadId']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        !$isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        // Get paginated BOR logs with relationships
        $logs = BorLog::where('personal_quote_id', $personalQuote->id)
            ->with(['insuranceProvider', 'personalQuote', 'signedDocument', 'document'])
            ->orderBy('created_at', 'desc')
            ->simplePaginate(15)
            ->withQueryString();
        
        // Total count for backward compatibility
        $total = BorLog::where('personal_quote_id', $personalQuote->id)->count();

        // Enhance each BOR log with document data
        $logs->getCollection()->transform(function ($borLog) {
            return $this->enrichBorLogWithDocuments($borLog);
        });

        return [$logs, $total];
    }

    /**
     * Enrich a single BOR log with document data
     *
     * @param BorLog $borLog
     * @return BorLog
     */
    public function enrichBorLogWithDocuments(BorLog $borLog): BorLog
    {
        try {
            $isDocumentUploaded = $borLog->document != null ? true : false;
            $isSignedDocument = $borLog->signedDocument != null ? true : false;
            $borLog->signed_pdf = $isSignedDocument ? collect([$borLog->signedDocument]) : null;
            $borLog->has_signed_pdf = $isSignedDocument;
            $borLog->uploaded_documents = $isDocumentUploaded ? collect([$borLog->document]) : null;
            $borLog->has_uploaded_documents = $isDocumentUploaded;

            $borLog->total_documents = $isDocumentUploaded || $isSignedDocument ? 1 : 0;
        } catch (\Exception $e) {
            // Log error but don't fail the entire request
            \Illuminate\Support\Facades\Log::warning('Failed to load documents for BOR log', [
                'bor_log_id' => $borLog->id,
                'error' => $e->getMessage()
            ]);
            
            // Add empty collections to prevent frontend errors
            $borLog->uploaded_documents = collect([]);
            $borLog->signed_pdf = collect([]);
            $borLog->has_uploaded_documents = false;
            $borLog->has_signed_pdf = false;
            $borLog->total_documents = 0;
        }

        return $borLog;
    }

    /**
     * Create a new BOR log
     *
     * @param array $data
     * @return array
     */
    public function createBorLog(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['personal_quote_id']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        !$isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        $data['personal_quote_id'] = $personalQuote->id;
        $data['status'] = BorStatusEnum::SIGNATURE_REQUESTED;

        $data['date_created'] = now();
        $data['email_sent'] = false;
        unset($data['lob']);
                
        $borLog = BorLog::create($data);

        // Send BOR request email
        $emailSent = false;

        if($quoteObject->advisor_id !== null) {
            $emailSent = $this->borEmailService->sendBorRequestEmail($borLog);
        }

        // Update email sent status
        // $borLog->update(['email_sent' => $emailSent]);
        
        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote', 'signedDocument', 'document']));

        // Update quote status to pending bor request if not already in a status that allows BOR request
        $statuses = [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::TransactionApproved];
        if(! in_array($quoteObject->quote_status_id, $statuses)) {
            $quoteObject->quote_status_id = QuoteStatusEnum::PendingBorRequest;
            $quoteObject->save();
        }
        
        return ['borLog' => $enrichedBorLog, 'emailSent' => $emailSent];
    }

    public function updateBorLog(array $data, $id)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['personal_quote_id']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        !$isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        $data['personal_quote_id'] = $personalQuote->id;
        unset($data['lob']);

        $borLog = BorLog::findOrFail($id);
        $borLog->update($data);

        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote', 'signedDocument', 'document']));
        
        return ['borLog' => $enrichedBorLog, 'emailSent' => false];
    }

    /**
     * Determine the appropriate BOR document type code based on lead LOB
     */
    public function determineBorDocumentType($quoteType): string | array
    {
        if (!$quoteType) {
            return 'BAL'; // Default to general BAL
        }

        // Map LOB to document type code
        $lobToDocumentType = [
            'Car' => DocumentTypeCode::BAL,
            'Bike' => DocumentTypeCode::BAL_BIKE,
            'Travel' => DocumentTypeCode::BAL_TRVL,
            'Home' => DocumentTypeCode::BAL_HOME,
            'Pet' => DocumentTypeCode::BAL_PET,
            'Health' => DocumentTypeCode::BAL_HLTH,
            'Life' => DocumentTypeCode::BAL_LIFE,
            'Cycle' => DocumentTypeCode::BAL_CYCLE,
            'Yacht' => DocumentTypeCode::BAL_YACHT,
            'Business' => array(DocumentTypeCode::BUS_BAL, DocumentTypeCode::BAL_BS),
            'Group Medical' => DocumentTypeCode::GM_BOL,
        ];

        return $lobToDocumentType[$quoteType] ?? 'BAL';
    }

    /**
     * Cancel a BOR log
     *
     * @param array $data
     * @param int $id
     * @return array
     */
    public function cancelBorLog(array $data, $id)
    {
        $borLog = BorLog::findOrFail($id);

        DB::beginTransaction();

        try {
            $oldStatus = $borLog->status;
            $success = $borLog->markAsCancelled($data['reason'], $data['notes']);
            
            if (!$success) {
                throw new \Exception('Failed to cancel BOR request. Please try again.');
            }

            DB::commit();
            // Enrich the updated BOR log with document data
            $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote', 'signedDocument', 'document']));
            
            return ['borLog' => $enrichedBorLog];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark a BOR log as done/completed
     *
     * @param array $data
     * @param int $id
     * @return array
     */
    public function markBorLogAsDone(array $data, $id)
    {
        $borLog = BorLog::findOrFail($id);

        if (!$borLog->allowsMarkingDone()) {
            throw new \Exception('This BOR cannot be marked as done in its current status: ' . $borLog->status);
        }

        DB::beginTransaction();

        try {
            $success = $borLog->markAsCompleted($data['notes']);

            if (!$success) {
                throw new \Exception('Failed to mark BOR as completed. Please try again.');
            }

            DB::commit();
            // Enrich the updated BOR log with document data
            $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote', 'signedDocument', 'document']));
            
            return ['borLog' => $enrichedBorLog];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload a BOR document
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $data
     * @param int $borLogId
     * @return array
     */
    public function uploadBorDocument($file, array $data, $borLogId)
    {
        $borLog = BorLog::findOrFail($borLogId);
        $personalQuote = $borLog->personalQuote;
        $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
        $quoteObject = checkPersonalQuotes($quoteType) ? $personalQuote : $this->getQuoteObject($quoteType, $personalQuote->quote_id);
        
        // Auto-determine document type code based on the lead's LOB if not provided
        $documentTypeCode = $data['document_type_code'] ?? $this->determineBorDocumentType($quoteType);
        
        // Get the document type for this LOB  
        $documentType = DocumentType::where('code', $documentTypeCode)
            ->where('is_active', 1)
            ->where("quote_type_id", $personalQuote->quote_type_id)
            ->when(isset($quoteObject->business_type_of_insurance_id), function ($query) use ($quoteObject) {
                $query->where('business_type_of_insurance_id', $quoteObject->business_type_of_insurance_id)->orWhere('business_type_of_insurance_id', null);
            })
            ->first();
        
        if (!$documentType) {
            throw new \Exception('Invalid document type for BOR upload: ' . $documentTypeCode);
        }

        DB::beginTransaction();

        try {
            // Leverage existing document upload service
            $quoteDocumentService = app(QuoteDocumentService::class);
            
            // Prepare data for existing upload logic
            $uploadData = [
                'document_type_code' => $documentTypeCode,
                'quote_uuid' => $borLog->bor_reference, // Use BOR reference as identifier
                'document_category' => $borLog->bor_reference,
            ];

            // Upload using existing service, leveraging polymorphic relationship
            $uploadedDocument = $quoteDocumentService->uploadQuoteDocument(
                $file,
                $uploadData,
                $quoteObject
            );

            if (!$uploadedDocument) {
                throw new \Exception('Failed to upload document');
            }

            // Update BOR log status
            $borLog->update([
                'status' => BorStatusEnum::DOCUMENT_UPLOADED,
                'quote_document_id' => $uploadedDocument->id,
                'document_id' => $uploadedDocument->doc_uuid,
                'user_agent' => getUserIpAddress(request()),
                'date_uploaded' => now(),
            ]);

            // Send completion notifications
            $this->borEmailService->sendBorCompletionEmail($borLog);

            // Send insurer notification if insurer email is available
            if ($borLog->insurance_contact_id != null) {
                LoggerService::info('Sending BOR Insurer Notification', [
                    'bor_ref_id' => $borLog->bor_reference,
                ]);
                $result_insurer = $this->borEmailService->sendBorInsurerNotification($borLog);
                if ($result_insurer) {
                    $borLog->update([
                        'email_sent' => 1,
                    ]);
                }
            }

            DB::commit();

            // Enrich the updated BOR log with document data
            $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote', 'signedDocument', 'document']));
            
            return [
                'borLog' => $enrichedBorLog,
                'document' => $uploadedDocument
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the representor for a given insurance provider
     *
     * @param int $insuranceProviderId
     * @return array
     */
    public function getRepresentor($insuranceProviderId, $quoteTypeId)
    {
        $representor = InsuranceProviderContact::where([
            ['insurance_provider_id', $insuranceProviderId], ['quote_type_id', $quoteTypeId]
        ])->get()->map(function ($contact) {
            return [
                'value' => $contact->id,
                'text' => "{$contact->department} - {$contact->emails}",
                'label' => "{$contact->department} - {$contact->emails}",
            ];
        });

        return $representor;
    }

    /**
     * fetch document types for a given quote type
     *
     * @param array $data
     * @return array
     */
    public function fetchDocumentTypes($data)
    {
        if (isset($data['quote_type_id'])) {
            $quoteType = QuoteTypes::getName($data['quote_type_id']);
            $borDocTypes = $this->determineBorDocumentType($quoteType->value);
            is_array($borDocTypes) ? $borDocTypes = $borDocTypes : $borDocTypes = [$borDocTypes];
        } else {
            $borDocTypes = [DocumentTypeCode::BAL_BIKE, DocumentTypeCode::BAL, DocumentTypeCode::BAL_HOME, DocumentTypeCode::BAL_LIFE, DocumentTypeCode::BAL_TRVL, DocumentTypeCode::BAL_HLTH, DocumentTypeCode::BAL_YACHT, DocumentTypeCode::BAL_CYCLE, DocumentTypeCode::BAL_PET, DocumentTypeCode::BAL_BS, DocumentTypeCode::GM_BOL, DocumentTypeCode::BUS_BAL];
        }

        $documentQuery = DocumentType::whereIn('code', $borDocTypes)
            ->when(isset($data['quote_type_id']), function ($query) use ($data) {
                $query->where('quote_type_id', $data['quote_type_id']);
            })
            ->when(!isset($data['business_type_of_insurance_id']), function ($query) use ($data) {
                $query->where('business_type_of_insurance_id', null);
            })
            ->where('is_active', 1);

        // Check if business_type_of_insurance_id filter should be applied
        if (isset($data['business_type_of_insurance_id'])) {
            $businessTypeId = $data['business_type_of_insurance_id'];

            // Clone the query to test if records exist with the business_type_of_insurance_id
            $clonedQuery = clone $documentQuery;
            $recordsExist = $clonedQuery->where('business_type_of_insurance_id', $businessTypeId)->exists();

            if ($recordsExist) {
                // Apply the filter if records exist
                $documentQuery->where('business_type_of_insurance_id', $businessTypeId);
            } else {
                // Fall back to null business_type_of_insurance_id if no records found
                $documentQuery->where('business_type_of_insurance_id', null);
            }
        }
        $documentTypes = $documentQuery->get();
        return $documentTypes;
    }
} 