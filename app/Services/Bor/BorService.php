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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BorService
{
    use GenericQueriesAllLobs;

    protected $borEmailService;
    protected $borPdfService;

    public function __construct(
        BorEmailService $borEmailService,
        BorPdfService $borPdfService,
    ) {
        $this->borEmailService = $borEmailService;
        $this->borPdfService = $borPdfService;
    }

    /**
     * Get BOR logs for a lead with embedded document data
     *
     * @return array
     */
    public function getBorLogs(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['leadId']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        ! $isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        if (! $personalQuote) {
            throw new \Exception('Personal quote not found for BOR logs');
        }

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
            LoggerService::warning('Failed to load documents for BOR log', [
                'bor_log_id' => $borLog->id,
                'error' => $e->getMessage(),
            ], $e);

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
     * @return array
     */
    public function createBorLog(array $data)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['personal_quote_id']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        ! $isPersonalQuote && $quoteObject->load('personalQuote');
        $personalQuote = $isPersonalQuote ? $quoteObject : $quoteObject->personalQuote;

        // if length of representor is 1, then set the insurance_contact_id to the first value by default
        if (isset($data['insurance_provider_id']) && $data['insurance_provider_id'] != null) {
            $representor = $this->getRepresentor($data['insurance_provider_id'], $personalQuote->quote_type_id);
            if (count($representor) == 1 && $data['insurance_contact_id'] == null) {
                $data['insurance_contact_id'] = $representor[0]['value'];
            }
        }

        $data['personal_quote_id'] = $personalQuote->id;
        $data['status'] = BorStatusEnum::SIGNATURE_REQUESTED;

        $data['date_created'] = now();
        $data['email_sent'] = false;
        unset($data['lob']);

        $borLog = BorLog::create($data);

        // Send BOR request email
        $emailSent = false;

        if ($quoteObject->advisor_id !== null) {
            $emailSent = $this->borEmailService->sendBorRequestEmail($borLog);
        }

        // Enrich the created BOR log with document data
        $enrichedBorLog = $this->enrichBorLogWithDocuments($borLog->fresh(['insuranceProvider', 'personalQuote', 'signedDocument', 'document']));

        // Update quote status to pending bor request if not already in a status that allows BOR request
        $statuses = [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::TransactionApproved];
        if (! in_array($quoteObject->quote_status_id, $statuses)) {
            $quoteObject->quote_status_id = QuoteStatusEnum::PendingBorRequest;
            $quoteObject->save();
        }

        return ['borLog' => $enrichedBorLog, 'emailSent' => $emailSent];
    }

    public function updateBorLog(array $data, $id)
    {
        $quoteObject = $this->getQuoteObject($data['lob'], $data['personal_quote_id']);
        $isPersonalQuote = checkPersonalQuotes(ucfirst($data['lob']));
        ! $isPersonalQuote && $quoteObject->load('personalQuote');
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
    public function determineBorDocumentType($quoteType): string|array
    {
        if (! $quoteType) {
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
            'Yacht' => DocumentTypeCode::BAL_YCHT,
            'Business' => [DocumentTypeCode::BUS_BAL, DocumentTypeCode::BAL_BS],
            'Group Medical' => DocumentTypeCode::GM_BOL,
        ];

        return $lobToDocumentType[$quoteType] ?? 'BAL';
    }

    /**
     * Cancel a BOR log
     *
     * @param  int  $id
     * @return array
     */
    public function cancelBorLog(array $data, $id)
    {
        $borLog = BorLog::findOrFail($id);

        DB::beginTransaction();

        try {
            $oldStatus = $borLog->status;
            $success = $borLog->markAsCancelled($data['reason'], $data['notes']);

            if (! $success) {
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
     * @param  int  $id
     * @return array
     */
    public function markBorLogAsDone(array $data, $id)
    {
        $borLog = BorLog::findOrFail($id);

        if (! $borLog->allowsMarkingDone()) {
            throw new \Exception('This BOR cannot be marked as done in its current status: '.$borLog->status);
        }

        DB::beginTransaction();

        try {
            $success = $borLog->markAsCompleted($data['notes']);

            if (! $success) {
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
     * @param  UploadedFile  $file
     * @param  int  $borLogId
     * @return array
     */
    public function uploadBorDocument($file, array $data, $borLogId)
    {
        $borLog = BorLog::findOrFail($borLogId);
        $personalQuote = $borLog->personalQuote;
        $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
        $quoteObject = checkPersonalQuotes($quoteType) ? $personalQuote : $this->getQuoteObject($quoteType, $personalQuote->quote_id);

        // Auto-determine document type code based on the lead's LOB if not provided
        $documentTypeCodeValue = $data['document_type_code'] ?? $this->determineBorDocumentType($quoteType);
        $documentTypeCode = is_array($documentTypeCodeValue) ? $documentTypeCodeValue : [$documentTypeCodeValue];
        // Get the document type for this LOB
        $documentType = DocumentType::whereIn('code', $documentTypeCode)
            ->where('is_active', 1)
            ->where('quote_type_id', $personalQuote->quote_type_id)
            ->when(isset($quoteObject->business_type_of_insurance_id), function ($query) use ($quoteObject) {
                $query->where('business_type_of_insurance_id', $quoteObject->business_type_of_insurance_id)->orWhere('business_type_of_insurance_id', null);
            })
            ->first();

        if (! $documentType) {
            throw new \Exception('Invalid document type for BOR upload: '.implode(', ', $documentTypeCode));
        }

        DB::beginTransaction();

        try {
            // Leverage existing document upload service
            $quoteDocumentService = app(QuoteDocumentService::class);

            // Prepare data for existing upload logic
            $uploadData = [
                'document_type_code' => $documentType->code,
                'quote_uuid' => $borLog->bor_reference, // Use BOR reference as identifier
                'document_category' => $borLog->bor_reference,
            ];

            // Upload using existing service, leveraging polymorphic relationship
            $uploadedDocument = $quoteDocumentService->uploadQuoteDocument(
                $file,
                $uploadData,
                $quoteObject
            );

            if (! $uploadedDocument) {
                throw new \Exception('Failed to upload document');
            }

            // Update BOR log status
            $borLog->update([
                'status' => BorStatusEnum::DOCUMENT_UPLOADED,
                'quote_document_id' => $uploadedDocument->id ?? null,
                'document_id' => $uploadedDocument->doc_uuid ?? null,
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
                'document' => $uploadedDocument,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the representor for a given insurance provider
     *
     * @param  int  $insuranceProviderId
     * @return array
     */
    public function getRepresentor($insuranceProviderId, $quoteTypeId)
    {
        $representor = InsuranceProviderContact::where([
            ['insurance_provider_id', $insuranceProviderId], ['quote_type_id', $quoteTypeId],
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
     * @param  array  $data
     * @return array
     */
    public function fetchDocumentTypes($data)
    {
        if (isset($data['quote_type_id'])) {
            $quoteType = QuoteTypes::getName($data['quote_type_id']);
            $borDocTypes = $this->determineBorDocumentType($quoteType->value);
            is_array($borDocTypes) ? $borDocTypes = $borDocTypes : $borDocTypes = [$borDocTypes];
        } else {
            $borDocTypes = [DocumentTypeCode::BAL_BIKE, DocumentTypeCode::BAL, DocumentTypeCode::BAL_HOME, DocumentTypeCode::BAL_LIFE, DocumentTypeCode::BAL_TRVL, DocumentTypeCode::BAL_HLTH, DocumentTypeCode::BAL_YCHT, DocumentTypeCode::BAL_CYCLE, DocumentTypeCode::BAL_PET, DocumentTypeCode::BAL_BS, DocumentTypeCode::GM_BOL, DocumentTypeCode::BUS_BAL];
        }

        $documentQuery = DocumentType::whereIn('code', $borDocTypes)
            ->when(isset($data['quote_type_id']), function ($query) use ($data) {
                $query->where('quote_type_id', $data['quote_type_id']);
            })
            ->when(! isset($data['business_type_of_insurance_id']), function ($query) {
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

    /**
     * Sign a BOR document
     *
     * @param  UploadedFile|string|null  $file
     */
    public function signDocument(array $data, $file = null): array
    {
        $borLog = BorLog::with('personalQuote')->where('bor_reference', $data['bor_ref_id'])->first();

        if (! $borLog) {
            throw new \Exception('BOR log not found');
        }

        $quote = $borLog->personalQuote;
        $quoteType = QuoteTypes::getName($quote->quote_type_id)->value;
        $quote = checkPersonalQuotes($quoteType) ? $quote : $this->getQuoteObject($quoteType, $quote->quote_id);

        if (! $quote) {
            throw new \Exception('Quote not found');
        }

        DB::beginTransaction();

        try {
            // Prepare document upload data
            $uploadData = [
                'quote_uuid' => $quote->uuid,
                'document_category' => $data['bor_ref_id'],
                'bor_signature' => true,
                'document_type_code' => $data['document_type_code'] ?? null,
            ];

            // Handle previous document deletion if new file is uploaded
            $previousDoc = $borLog->document;
            if ($previousDoc && $previousDoc->doc_url && $file) {
                Storage::disk('azureIMPrivate')->delete($previousDoc->doc_url);
                $previousDoc->delete();
            }

            // Upload new document if provided
            $document = null;
            if ($file) {
                // Add is_base_64 flag to upload data for proper handling
                $uploadData['is_base_64'] = $data['is_base_64'] ?? 0;
                $uploadData['file_name'] = $data['file_name'] ?? null;

                $quoteDocumentService = app(QuoteDocumentService::class);
                $document = $quoteDocumentService->uploadQuoteDocument($file, $uploadData, $quote);
            }

            $docIsPresent = isset($document) && ! is_null($document);

            // Update BOR log
            $updateData = [
                'quote_document_id' => ($docIsPresent && $document) ? ($document->id ?? null) : $borLog->quote_document_id,
                'document_id' => ($docIsPresent && $document) ? ($document->doc_uuid ?? null) : $borLog->document_id,
                'user_agent' => getUserIpAddress(request()),
                'download_clicked' => $borLog->download_clicked == 1 ? 1 : ($data['download_clicked'] ?? 0),
                'insurer_name' => ! empty(trim($data['insurer_name'] ?? '')) ? $data['insurer_name'] : $borLog->insurer_name,
                'policy_number' => ! empty(trim($data['policy_number'] ?? '')) ? $data['policy_number'] : $borLog->policy_number,
                'status' => $docIsPresent ? BorStatusEnum::DOCUMENT_SIGNED : $borLog->status,
                'date_signed' => $docIsPresent ? now() : $borLog->date_signed,
            ];

            $borLog->update($updateData);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Document signed successfully',
                'data' => $document,
                'borLog' => $borLog->fresh(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload a quote document for BOR
     *
     * @param  UploadedFile|string  $file
     */
    public function uploadQuoteDocument(array $data, $file): array
    {
        $quoteType = $data['quote_type'];
        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);

        if (! $quote) {
            throw new \Exception('Quote not found');
        }

        // Ensure is_base_64 flag is set in data for proper handling
        $data['is_base_64'] = $data['is_base_64'] ?? 0;

        $quoteDocumentService = new QuoteDocumentService;
        $document = $quoteDocumentService->uploadQuoteDocument($file, $data, $quote);

        return [
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document' => $document,
        ];
    }

    /**
     * Delete a BOR document
     */
    public function deleteDocument(array $data): array
    {
        $borLog = BorLog::with('personalQuote.documents')->where('bor_reference', $data['bor_ref_id'])->first();

        if (! $borLog) {
            throw new \Exception('BOR log not found');
        }

        $quote = $borLog->personalQuote;
        if (! $quote) {
            throw new \Exception('Personal quote not found');
        }

        $quoteName = QuoteTypes::getName($quote->quote_type_id);
        $isPersonalQuote = checkPersonalQuotes($quoteName->value);
        $quote = $isPersonalQuote ? $this->getQuoteObject($quoteName->value, $quote->id) : $this->getQuoteObject($quoteName->value, $quote->quote_id);

        if (! $quote) {
            throw new \Exception('Quote not found');
        }

        $deleteData = [
            'doc_name' => $data['doc_name'],
            'doc_uuid' => $data['doc_uuid'],
            'document_category' => $data['bor_ref_id'],
        ];

        $quoteDocumentService = new QuoteDocumentService;
        $result = $quoteDocumentService->deleteBorDocument($quote, $deleteData);

        return [
            'success' => true,
            'message' => 'Document deleted successfully',
            'data' => $result,
        ];
    }

    /****************************************** SSE ******************************************/

    /**
     * Handle SSE streaming for BOR log updates
     */
    public function streamBorLogUpdates(string $borRefId): callable
    {
        return function () use ($borRefId) {
            // Disable all output buffering for real-time streaming
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set up unbuffered output
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }

            // Critical PHP settings for SSE
            ini_set('output_buffering', 0);
            ini_set('implicit_flush', 1);
            ini_set('zlib.output_compression', 0);
            ini_set('max_execution_time', 300); // 5 minutes for SSE
            ini_set('memory_limit', '256M');

            // Ignore user disconnect to continue processing
            ignore_user_abort(true);

            $lastDataHash = null;
            $maxIterations = 60; // Maximum 5 minutes (60 * 5 seconds)
            $iteration = 0;

            LoggerService::info('SSE BOR stream started', ['bor_ref_id' => $borRefId]);

            // Send initial connection confirmation
            echo "event: connected\n";
            echo 'data: '.json_encode(['message' => 'SSE connection established', 'bor_ref_id' => $borRefId])."\n\n";
            flush();

            while ($iteration < $maxIterations) {
                // Enhanced connection status check with detailed logging
                $connectionStatus = connection_status();
                $connectionAborted = connection_aborted();

                if ($connectionAborted || $connectionStatus !== CONNECTION_NORMAL) {
                    LoggerService::warning('SSE BOR client disconnected', [
                        'bor_ref_id' => $borRefId,
                        'iteration' => $iteration,
                        'connection_status' => $connectionStatus,
                        'connection_aborted' => $connectionAborted,
                        'connection_status_text' => $this->getConnectionStatusText($connectionStatus),
                        'memory_usage' => memory_get_usage(true),
                        'execution_time' => (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']).'s',
                    ]);

                    // Try to send a final disconnect event before breaking
                    try {
                        echo "event: disconnect\n";
                        echo 'data: '.json_encode([
                            'message' => 'Client disconnected',
                            'iteration' => $iteration,
                            'reason' => $this->getConnectionStatusText($connectionStatus),
                        ])."\n\n";
                        flush();
                    } catch (\Exception $e) {
                        LoggerService::error('Failed to send disconnect event', ['error' => $e->getMessage()]);
                    }
                    break;
                }

                // Check connection status periodically with more details
                if ($iteration % 5 == 0) {
                    LoggerService::info('SSE BOR connection status check', [
                        'bor_ref_id' => $borRefId,
                        'iteration' => $iteration,
                        'connection_status' => $connectionStatus,
                        'connection_status_text' => $this->getConnectionStatusText($connectionStatus),
                        'memory_usage' => memory_get_usage(true),
                        'peak_memory' => memory_get_peak_usage(true),
                        'execution_time' => (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']).'s',
                    ]);
                }

                $borLog = BorLog::where('bor_reference', $borRefId)->first();

                if (! $borLog) {
                    echo "event: error\n";
                    echo 'data: '.json_encode(['error' => 'BOR log not found'])."\n\n";
                    flush();
                    break;
                }

                // Create a hash of the current data to detect changes
                $currentDataHash = md5(json_encode($borLog->toArray()));

                // Only send data if it has changed
                if ($lastDataHash !== $currentDataHash) {
                    echo "event: borUpdate\n";
                    echo 'data: '.json_encode(['data' => $borLog])."\n\n";
                    flush();

                    LoggerService::info('SSE BOR data sent', [
                        'bor_ref_id' => $borRefId,
                        'status' => $borLog->status,
                        'iteration' => $iteration,
                    ]);

                    $lastDataHash = $currentDataHash;
                } else {
                    // Send a heartbeat to keep connection alive without duplicating data
                    echo "event: heartbeat\n";
                    echo 'data: '.json_encode([
                        'timestamp' => now()->toISOString(),
                        'iteration' => $iteration,
                        'server_time' => time(),
                        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).'MB',
                    ])."\n\n";

                    // Ensure data is sent immediately
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();

                    LoggerService::info('SSE BOR heartbeat sent', [
                        'bor_ref_id' => $borRefId,
                        'iteration' => $iteration,
                        'connection_status' => $connectionStatus,
                    ]);
                }

                // Check if BOR process is completed
                if ($borLog->status == BorStatusEnum::DOCUMENT_SIGNED || $borLog->status == BorStatusEnum::DOCUMENT_UPLOADED) {
                    LoggerService::info('SSE BOR process completed, ending stream', [
                        'bor_ref_id' => $borRefId,
                        'status' => $borLog->status,
                    ]);
                    echo "event: completed\n";
                    echo 'data: '.json_encode(['message' => 'BOR process completed', 'data' => $borLog])."\n\n";
                    flush();
                    break;
                }

                $iteration++;

                // Use a shorter sleep with connection check
                for ($i = 0; $i < 5; $i++) {
                    sleep(1);
                    // Quick connection check during sleep
                    if (connection_aborted()) {
                        LoggerService::info('SSE BOR connection lost during sleep', [
                            'bor_ref_id' => $borRefId,
                            'iteration' => $iteration,
                            'sleep_second' => $i + 1,
                        ]);
                        break 2; // Break out of both loops
                    }
                }
            }

            if ($iteration >= $maxIterations) {
                LoggerService::info('SSE BOR stream timeout', ['bor_ref_id' => $borRefId]);
                echo "event: timeout\n";
                echo 'data: '.json_encode(['message' => 'Stream timeout reached'])."\n\n";
                flush();
            }

            LoggerService::info('SSE BOR stream ended', ['bor_ref_id' => $borRefId]);
        };
    }

    /**
     * Get the SSE headers for streaming response
     */
    public function getSseHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable Nginx buffering
            'X-Proxy-Buffering' => 'no', // Disable proxy buffering
            'X-Azure-FDID' => 'no-buffer', // Azure Front Door hint
            'Transfer-Encoding' => 'chunked', // Force chunked encoding
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Cache-Control',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Expose-Headers' => 'Content-Type, Cache-Control, Connection',
        ];
    }

    /**
     * Get human-readable connection status text
     */
    private function getConnectionStatusText($status)
    {
        switch ($status) {
            case CONNECTION_NORMAL:
                return 'NORMAL';
            case CONNECTION_ABORTED:
                return 'ABORTED';
            case CONNECTION_TIMEOUT:
                return 'TIMEOUT';
            default:
                return 'UNKNOWN_'.$status;
        }
    }

    /****************************************** SSE ******************************************/
}
