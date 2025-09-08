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

    /**
     * @var BorPdfService
     */
    private $borPdfService;

    private $borService;

    /**
     * @var BorEmailService
     */
    private $borEmailService;

    /**
     * @var QuoteDocumentService
     */
    private $quoteDocumentService;

    public function __construct(
        BorPdfService $borPdfService,
        QuoteDocumentService $quoteDocumentService,
        BorService $borService,
        BorEmailService $borEmailService
    ) {
        $this->borPdfService = $borPdfService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->borService = $borService;
        $this->borEmailService = $borEmailService;
    }


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
            $response = new StreamedResponse(function() use ($borRefId) {
                if (ob_get_level() == 0) ob_start();
                
                $lastDataHash = null;
                $maxIterations = 120; // Maximum 10 minutes (120 * 5 seconds)
                $iteration = 0;
                
                LoggerService::info('SSE BOR stream started', ['bor_ref_id' => $borRefId]);
                
                while ($iteration < $maxIterations) {
                    $borLog = BorLog::where('bor_reference', $borRefId)->first();
                    
                    if (!$borLog) {
                        echo "event: error\n";
                        echo "data: " . json_encode(['error' => 'BOR log not found']) . "\n\n";
                        break;
                    }
                    
                    // Create a hash of the current data to detect changes
                    $currentDataHash = md5(json_encode($borLog->toArray()));
                    
                    // Only send data if it has changed
                    if ($lastDataHash !== $currentDataHash) {
                        echo "event: borUpdate\n";
                        echo "data: " . json_encode(['data' => $borLog]) . "\n\n";
                        
                        LoggerService::info('SSE BOR data sent', [
                            'bor_ref_id' => $borRefId,
                            'status' => $borLog->status,
                            'iteration' => $iteration
                        ]);
                        
                        $lastDataHash = $currentDataHash;
                    } else {
                        // Send a heartbeat to keep connection alive without duplicating data
                        echo "event: heartbeat\n";
                        echo "data: " . json_encode(['timestamp' => now()->toISOString()]) . "\n\n";
                    }

                    // Flush the output buffer
                    if (ob_get_length()) {
                        ob_flush();
                        flush();
                    }

                    // Check if the client has disconnected
                    if (connection_aborted()) {
                        LoggerService::info('SSE BOR client disconnected', ['bor_ref_id' => $borRefId]);
                        break;
                    }
                    
                    // Check if BOR process is completed
                    if ($borLog->status === BorStatusEnum::DOCUMENT_SIGNED || $borLog->status === BorStatusEnum::DOCUMENT_UPLOADED) {
                        LoggerService::info('SSE BOR process completed, ending stream', [
                            'bor_ref_id' => $borRefId,
                            'status' => $borLog->status
                        ]);
                        echo "event: completed\n";
                        echo "data: " . json_encode(['message' => 'BOR process completed', 'data' => $borLog]) . "\n\n";
                        if (ob_get_length()) {
                            ob_flush();
                            flush();
                        }
                        break;
                    }

                    $iteration++;
                    sleep(5);
                }
                
                if ($iteration >= $maxIterations) {
                    LoggerService::info('SSE BOR stream timeout', ['bor_ref_id' => $borRefId]);
                    echo "event: timeout\n";
                    echo "data: " . json_encode(['message' => 'Stream timeout reached']) . "\n\n";
                }

                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                
                LoggerService::info('SSE BOR stream ended', ['bor_ref_id' => $borRefId]);
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // Disable Nginx buffering
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Credentials' => 'true',
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
            $pdf = $this->borPdfService->generatePreviewBorPdf($borLog);

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
                $document = $this->quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->all(), $quote);
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
        if($request->has('quote_type_id')) {
            $quoteType = QuoteTypes::getName($request->input('quote_type_id'));
            $borDocTypes = $this->borService->determineBorDocumentType($quoteType->value);
            is_array($borDocTypes) ? $borDocTypes = $borDocTypes : $borDocTypes = [$borDocTypes];
        } else {
            $borDocTypes = [DocumentTypeCode::BAL_BIKE, DocumentTypeCode::BAL, DocumentTypeCode::BAL_HOME, DocumentTypeCode::BAL_LIFE, DocumentTypeCode::BAL_TRVL, DocumentTypeCode::BAL_HLTH, DocumentTypeCode::BAL_YACHT, DocumentTypeCode::BAL_CYCLE, DocumentTypeCode::BAL_PET, DocumentTypeCode::BAL_BS, DocumentTypeCode::GM_BOL, DocumentTypeCode::BUS_BAL];
        }

        $documentQuery = DocumentType::whereIn('code', $borDocTypes)
            ->when(($request->has('quote_type_id')), function ($query) use ($request) {
                $query->where('quote_type_id', $request->input('quote_type_id'));
            })
            ->when(!$request->has('business_type_of_insurance_id'), function ($query) use ($request) {
                $query->where('business_type_of_insurance_id', null);
            })
            ->where('is_active', 1);

        // Check if business_type_of_insurance_id filter should be applied
        if ($request->has('business_type_of_insurance_id')) {
            $businessTypeId = $request->input('business_type_of_insurance_id');
            
            // Clone the query to test if records exist with the business_type_of_insurance_id
            $testQuery = clone $documentQuery;
            $recordsExist = $testQuery->where('business_type_of_insurance_id', $businessTypeId)->exists();
            
            if ($recordsExist) {
                // Apply the filter if records exist
                $documentQuery->where('business_type_of_insurance_id', $businessTypeId);
            } else {
                // Fall back to null business_type_of_insurance_id if no records found
                $documentQuery->where('business_type_of_insurance_id', null);
            }
        }

        $documentTypes = $documentQuery->get();
        return response()->json(['data' => $documentTypes]);
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

        $document = $this->quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->all(), $quote);

        return new QuoteDocumentResource($document);
    }

    public function deleteDocument(Request $request)
    {
        $borLog = BorLog::with('personalQuote', 'personalQuote.documents')->where('bor_reference', $request->bor_ref_id)->first();
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
        return $this->quoteDocumentService->deleteBorDocument($quote, $data);
    }

    public function borCompletionEmailTrigger($borRefId)
    {
        LoggerService::info('BOR Completion Email Trigger', [
            'bor_ref_id' => $borRefId,
        ]);
        $borLog = BorLog::where('bor_reference', $borRefId)->first();
        $result = $this->borEmailService->sendBorCompletionEmail($borLog);
        if($borLog->insurance_contact_id != null) {
            LoggerService::info('Sending BOR Insurer Notification', [
                'bor_ref_id' => $borRefId,
            ]);
            $result_insurer = $this->borEmailService->sendBorInsurerNotification($borLog);
            if($result_insurer) {
                $borLog->update([
                    'email_sent' => 1,
                ]);
            }
        }
        return response()->json(['message' => 'success', 'result' => $result]);
    }
}
