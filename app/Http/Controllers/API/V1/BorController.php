<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteDocumentResource;
use App\Models\BorLog;
use App\Models\DocumentType;
use App\Services\Bor\BorEmailService;
use App\Services\Bor\BorPdfService;
use App\Services\Bor\BorService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BorController extends Controller
{
    use GenericQueriesAllLobs;

    private $borService;


    public function __construct(
        BorService $borService,
    ) {
        $this->borService = $borService;
    }

    /**
     * This function is only used to get uploaded documents for a BOR log for frontend display
     *
     * @param string $borRefId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBorLog($borRefId){
        try {
            $borLog = BorLog::where('bor_reference', $borRefId)->first();
            if(!$borLog){
                return response()->json(['error' => 'BOR log not found'], 404);
            }
            $borLog->load('insuranceProvider', 'personalQuote');
            $enrichedBorLog = $this->borService->enrichBorLogWithDocuments($borLog);
            
            return response()->json(['data' => $enrichedBorLog['uploaded_documents']]);
        } catch (Exception $th) {
            LoggerService::error('Failed to get BOR log', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => request()->all(),
            ]);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function getBorLogSSE($borRefId)
    {
        try {
            $response = new StreamedResponse(function () use ($borRefId) {
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
                ini_set('max_execution_time', 1800); // 30 minutes for SSE
                ini_set('memory_limit', '256M');
                
                // Ignore user disconnect to continue processing
                ignore_user_abort(true);

                $lastDataHash = null;
                $maxIterations = 200; // Maximum 10 minutes (200 * 3 seconds)
                $iteration = 0;

                LoggerService::info('SSE BOR stream started', ['bor_ref_id' => $borRefId]);

                // Send initial connection confirmation
                echo "event: connected\n";
                echo "data: " . json_encode(['message' => 'SSE connection established', 'bor_ref_id' => $borRefId]) . "\n\n";
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
                            'execution_time' => (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . 's'
                        ]);
                        
                        // Try to send a final disconnect event before breaking
                        try {
                            echo "event: disconnect\n";
                            echo "data: " . json_encode([
                                'message' => 'Client disconnected', 
                                'iteration' => $iteration,
                                'reason' => $this->getConnectionStatusText($connectionStatus)
                            ]) . "\n\n";
                            flush();
                        } catch (Exception $e) {
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
                            'execution_time' => (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . 's'
                        ]);
                    }

                    $borLog = BorLog::where('bor_reference', $borRefId)->first();

                    if (!$borLog) {
                        echo "event: error\n";
                        echo "data: " . json_encode(['error' => 'BOR log not found']) . "\n\n";
                        flush();
                        break;
                    }

                    // Create a hash of the current data to detect changes
                    $currentDataHash = md5(json_encode($borLog->toArray()));

                    // Only send data if it has changed
                    if ($lastDataHash !== $currentDataHash) {
                        echo "event: borUpdate\n";
                        echo "data: " . json_encode(['data' => $borLog]) . "\n\n";
                        flush();
                        
                        LoggerService::info('SSE BOR data sent', [
                            'bor_ref_id' => $borRefId,
                            'status' => $borLog->status,
                            'iteration' => $iteration
                        ]);

                        $lastDataHash = $currentDataHash;
                    } else {
                        // Send a heartbeat to keep connection alive without duplicating data
                        echo "event: heartbeat\n";
                        echo "data: " . json_encode([
                            'timestamp' => now()->toISOString(), 
                            'iteration' => $iteration,
                            'server_time' => time(),
                            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
                        ]) . "\n\n";
                        
                        // Ensure data is sent immediately
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                        
                        LoggerService::info('SSE BOR heartbeat sent', [
                            'bor_ref_id' => $borRefId,
                            'iteration' => $iteration,
                            'connection_status' => $connectionStatus
                        ]);
                    }

                    // Check if BOR process is completed
                    if ($borLog->status == BorStatusEnum::DOCUMENT_SIGNED || $borLog->status == BorStatusEnum::DOCUMENT_UPLOADED) {
                        LoggerService::info('SSE BOR process completed, ending stream', [
                            'bor_ref_id' => $borRefId,
                            'status' => $borLog->status
                        ]);
                        echo "event: completed\n";
                        echo "data: " . json_encode(['message' => 'BOR process completed', 'data' => $borLog]) . "\n\n";
                        flush();
                        break;
                    }

                    $iteration++;
                    
                    // Use a shorter sleep with connection check
                    for ($i = 0; $i < 3; $i++) {
                        sleep(1);
                        // Quick connection check during sleep
                        if (connection_aborted()) {
                            LoggerService::info('SSE BOR connection lost during sleep', [
                                'bor_ref_id' => $borRefId,
                                'iteration' => $iteration,
                                'sleep_second' => $i + 1
                            ]);
                            break 2; // Break out of both loops
                        }
                    }
                }

                if ($iteration >= $maxIterations) {
                    LoggerService::info('SSE BOR stream timeout', ['bor_ref_id' => $borRefId]);
                    echo "event: timeout\n";
                    echo "data: " . json_encode(['message' => 'Stream timeout reached']) . "\n\n";
                    flush();
                }

                LoggerService::info('SSE BOR stream ended', ['bor_ref_id' => $borRefId]);
            }, 200, [
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
            ]);
            return $response;
        } catch (Exception $th) {
            LoggerService::error('Failed to get BOR log SSE', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => request()->all(),
                'bor_ref_id' => $borRefId,
            ]);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $refId = $request->input('bor_ref_id');
            $borLog = BorLog::where('bor_reference', $refId)->first();
            $borPdfService = new BorPdfService();
            $pdf = $borPdfService->generatePreviewBorPdf($borLog);

            return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf['pdf']->download()), 'name' => $pdf['name']]);
        } catch (\Throwable $th) {
            LoggerService::error('Failed to generate PDF', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function signDocument(Request $request)
    {
        try {
            $borLog = BorLog::where('bor_reference', $request->input('bor_ref_id'))->first();
            $quote = $borLog->personalQuote;
            $quoteType = QuoteTypes::getName($quote->quote_type_id)->value;
            $quote = checkPersonalQuotes($quoteType) ? $quote : $this->getQuoteObject($quoteType, $quote->quote_id);

            $request->merge(['quote_uuid' => $quote->uuid]);
            $request->merge(['document_category' => $request->input('bor_ref_id')]);
            $request->merge(['bor_signature' => true]);
            $previousDoc = $borLog->document;

            if($previousDoc && $previousDoc->doc_url && $request->hasFile('file')) {
                Storage::disk('azureIM')->delete($previousDoc->doc_url);
                $previousDoc->delete();
            }

            $document = null;
            if($request->hasFile('file')) {
                $quoteDocumentService = new QuoteDocumentService();
                $document = $quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->all(), $quote);
            }

            $docIsPresent = isset($document) && !is_null($document);
            
            $borLog->update([
                'quote_document_id' => $docIsPresent ? $document->id : $borLog->quote_document_id,
                'document_id' => $docIsPresent ? $document->doc_uuid : $borLog->document_id,
                'user_agent' => getUserIpAddress($request),
                'download_clicked' => $borLog->download_clicked == 1 ? 1 :$request->download_clicked ?? 0,
                'insurer_name' => $request->insurer_name,
                'policy_number' => $request->policy_number,
                'status' => $docIsPresent ? BorStatusEnum::DOCUMENT_SIGNED : $borLog->status,
                'date_signed' => $docIsPresent ? now() : $borLog->date_signed,
            ]);

            return response()->json(['message' => 'success', 'data' => $document]);
        } catch (Exception $th) {
            LoggerService::error('Failed to sign document', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json(['message' => 'failed', 'error' => $th->getMessage()], 500);
        }
    }

    public function getDocumentTypes(Request $request)
    {
        try {
            $data = $request->only('quote_type_id', 'business_type_of_insurance_id');
            $documentTypes = $this->borService->fetchDocumentTypes($data);
            return response()->json(['data' => $documentTypes]);
        } catch (\Throwable $th) {
            LoggerService::error('Failed to get document types', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'failed', 'error' => $th->getMessage()], 500);
        }
    }

    public function uploadDocument(Request $request)
    {
        $quoteType = $request->quote_type;

        if (
            ! $request->hasFile('file') ||
            ! ($quote = $this->getQuoteObject($quoteType, $request->quote_uuid))
        ) {
            return response()->json(['error' => 'Quote not found'], 404);
        }

        $quoteDocumentService = new QuoteDocumentService();
        $document = $quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->all(), $quote);

        return new QuoteDocumentResource($document);
    }

    public function deleteDocument(Request $request)
    {
        $borLog = BorLog::with('personalQuote.documents')->where('bor_reference', $request->bor_ref_id)->first();
        $quote = $borLog->personalQuote;
        $quoteName = QuoteTypes::getName($quote->quote_type_id);
        $isPersonalQuote = checkPersonalQuotes($quoteName->value);
        $quote = $isPersonalQuote ? $this->getQuoteObject($quoteName->value, $quote->id) : $this->getQuoteObject($quoteName->value, $quote->quote_id);
        if(!$quote) {
            return response()->json(['message' => 'Quote not found'],200);
        }

        $data = [
            'doc_name' => $request->doc_name,
            'doc_uuid' => $request->doc_uuid,
            'document_category' => $request->bor_ref_id,
        ];
        $quoteDocumentService = new QuoteDocumentService();
        return $quoteDocumentService->deleteBorDocument($quote, $data);
    }

    public function borCompletionEmailTrigger($borRefId)
    {
        LoggerService::info('BOR Completion Email Trigger', [
            'bor_ref_id' => $borRefId,
        ]);
        $borLog = BorLog::where('bor_reference', $borRefId)->first();
        $borEmailService = new BorEmailService();
        $result = $borEmailService->sendBorCompletionEmail($borLog);
        if($borLog->insurance_contact_id != null) {
            LoggerService::info('Sending BOR Insurer Notification', [
                'bor_ref_id' => $borRefId,
            ]);
            $isInsurerEmailSent = $borEmailService->sendBorInsurerNotification($borLog);
            if($isInsurerEmailSent) {
                $borLog->update([
                    'email_sent' => 1,
                ]);
            }
        }
        return response()->json(['message' => 'success', 'result' => $result]);
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
                return 'UNKNOWN_' . $status;
        }
    }
}
