<?php

namespace App\Services\Bor;

use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\BorLog;
use App\Models\DocumentType;
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
        $logs = BorLog::where('lead_id', $personalQuote->id)
            ->with(['insuranceProvider', 'personalQuote'])
            ->orderBy('created_at', 'desc')
            ->simplePaginate(15)
            ->withQueryString();
        
        // Total count for backward compatibility
        $total = BorLog::where('lead_id', $personalQuote->id)->count();

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
            $personalQuote = $borLog->personalQuote;
            $borRefId = $borLog->bor_reference;
            if (!$personalQuote) {
                // Add empty document collections if no personal quote found
                $borLog->uploaded_documents = collect([]);
                $borLog->signed_pdf = collect([]);
                return $borLog;
            }

            // Get the quote object to access documents
            $quoteName = QuoteTypes::getName($personalQuote->quote_type_id);
            $isPersonalQuote = checkPersonalQuotes($quoteName->value);
            $quoteObject = $isPersonalQuote ? $this->getQuoteObject($quoteName->value, $personalQuote->id) : $this->getQuoteObject($quoteName->value, $personalQuote->quote_id);

            // Filter uploaded documents (BOR letters)
            $quoteObject->load('documents');
            $uploadedDocuments = $quoteObject->documents->filter(function ($doc) use ($borRefId) {
                $code = $doc->document_type_code;
                $allowedCodes = [
                    DocumentTypeCode::BAL,
                    DocumentTypeCode::BAL_BIKE,
                    DocumentTypeCode::BAL_TRVL,
                    DocumentTypeCode::BAL_HOME,
                    DocumentTypeCode::BAL_HLTH,
                    DocumentTypeCode::BAL_PET,
                    DocumentTypeCode::BAL_YACHT,
                    DocumentTypeCode::BAL_CYCLE,
                    DocumentTypeCode::BAL_LIFE,
                ];
                return in_array($code, $allowedCodes) && $doc->document_category == $borRefId;
            })->map(function ($doc) use ($borRefId) {
                return [
                    'doc_name' => $doc->doc_name,
                    'doc_url' => $doc->doc_url,
                    'doc_uuid' => $doc->doc_uuid,
                    'document_type_text' => $doc->document_type_text,
                    'document_type_code' => $doc->document_type_code,
                    'doc_mime_type' => $doc->doc_mime_type,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                ];
            });

            // Filter signed PDF documents
            $signedPdf = $personalQuote->documents->filter(function ($doc) use($borRefId) {
                $code = $doc->document_type_code;
                return $code == DocumentTypeCode::BOR_SIGN && $doc->document_category == $borRefId;
            });

            // Add document collections to the BOR log object
            $borLog->uploaded_documents = $uploadedDocuments->values()->toArray();
            $borLog->signed_pdf = $signedPdf->values()->toArray();
            
            // Add document metadata for easy access
            $borLog->has_uploaded_documents = $uploadedDocuments->isNotEmpty();
            $borLog->has_signed_pdf = $signedPdf->isNotEmpty();
            $borLog->total_documents = $uploadedDocuments->count() + $signedPdf->count();
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
        $quoteObject = $this->getQuoteObject($data['lob'], $data['lead_id']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        !$isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        $data['lead_id'] = $personalQuote->id;
        $data['status'] = BorStatusEnum::SIGNATURE_REQUESTED;

        $data['date_created'] = now();
        $data['email_sent'] = false;
        unset($data['lob']);
                
        $borLog = BorLog::create($data);

        // Send BOR request email
        $emailSent = $this->borEmailService->sendBorRequestEmail($borLog);

        // Update email sent status
        // $borLog->update(['email_sent' => $emailSent]);
        
        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));

        $personalQuote->quote_status_id = QuoteStatusEnum::PendingBorRequest;
        $personalQuote->save();
        
        return ['borLog' => $enrichedBorLog, 'emailSent' => $emailSent];
    }

    public function updateBorLog(array $data, $id)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['lead_id']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        !$isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        $data['lead_id'] = $personalQuote->id;
        unset($data['lob']);

        $borLog = BorLog::findOrFail($id);
        $borLog->update($data);

        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));
        
        return ['borLog' => $enrichedBorLog, 'emailSent' => false];
    }

    /**
     * Determine the appropriate BOR document type code based on lead LOB
     */
    public function determineBorDocumentType($quoteType): string
    {
        if (!$quoteType) {
            return 'BAL'; // Default to general BAL
        }

        // Map LOB to document type code
        $lobToDocumentType = [
            'car' => DocumentTypeCode::BAL,
            'bike' => DocumentTypeCode::BAL_BIKE,
            'travel' => DocumentTypeCode::BAL_TRVL,
            'home' => DocumentTypeCode::BAL_HOME,
            'pet' => DocumentTypeCode::BAL_PET,
            'health' => DocumentTypeCode::BAL_HLTH,
            'life' => DocumentTypeCode::BAL_LIFE,
            'cycle' => DocumentTypeCode::BAL_CYCLE,
            'yacht' => DocumentTypeCode::BAL_YACHT,
            'business' => DocumentTypeCode::BAL_BS,
        ];

        return $lobToDocumentType[strtolower($quoteType)] ?? 'BAL';
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

        if (!$borLog->allowsCancellation()) {
            throw new \Exception('This BOR cannot be cancelled in its current status: ' . $borLog->status);
        }

        DB::beginTransaction();

        try {
            $oldStatus = $borLog->status;
            $success = $borLog->markAsCancelled($data['reason']);
            
            if (!$success) {
                throw new \Exception('Failed to cancel BOR request. Please try again.');
            }

            // Send cancellation notification
            $this->borEmailService->sendBorStatusUpdateEmail(
                $borLog,
                $oldStatus,
                BorStatusEnum::CANCELLED
            );

            DB::commit();

            // Enrich the updated BOR log with document data
            $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));
            
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
            $oldStatus = $borLog->status;
            $success = $borLog->markAsCompleted();

            if (!$success) {
                throw new \Exception('Failed to mark BOR as completed. Please try again.');
            }

            // Save optional completion notes if provided
            if (!empty($data['notes'])) {
                $borLog->completion_notes = $data['notes'];
                $borLog->save();
            }

            // Send completion notifications
            $this->borEmailService->sendBorCompletionNotifications($borLog);

            DB::commit();

            // Enrich the updated BOR log with document data
            $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));
            
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
        $quoteObject = $this->getQuoteObject($quoteType, $personalQuote->quote_id);
        
        // Auto-determine document type code based on the lead's LOB if not provided
        $documentTypeCode = $data['document_type_code'] ?? $this->determineBorDocumentType($quoteType);
        
        // Get the document type for this LOB  
        $documentType = DocumentType::where('code', $documentTypeCode)
            ->where('is_active', 1)
            ->whereNull('business_type_of_insurance_id')
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
                'date_uploaded' => now(),
            ]);

            // Send completion notifications
            $this->borEmailService->sendBorCompletionEmail($borLog);
            
            // Send insurer notification if insurer email is available
            if ($borLog->insurer_name) {
                $this->borEmailService->sendBorInsurerNotification(
                    $borLog,
                    'insurer@example.com' // This should be configurable or retrieved from insurer data
                );
            }

            DB::commit();

            // Enrich the updated BOR log with document data
            $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote']));
            
            return [
                'borLog' => $enrichedBorLog,
                'document' => $uploadedDocument
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 