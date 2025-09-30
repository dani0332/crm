<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\BorStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bor\BorSignRequest;
use App\Http\Resources\QuoteDocumentResource;
use App\Models\BorLog;
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
     * @param  string  $borRefId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBorLog($borRefId)
    {
        try {
            $borLog = BorLog::where('bor_reference', $borRefId)->first();
            if (! $borLog) {
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
            $streamCallback = $this->borService->streamBorLogUpdates($borRefId);
            $headers = $this->borService->getSseHeaders();
            
            $response = new StreamedResponse($streamCallback, 200, $headers);

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
            $borPdfService = new BorPdfService;
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

    public function signDocument(BorSignRequest $request)
    {
        try {
            $file = null;
            if ($request->hasFile('file')) {
                $file = data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file');
            }

            $result = $this->borService->signDocument($request->validated(), $file);

            return response()->json([
                'message' => $result['message'],
                'data' => $result['data']
            ]);
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

        $quoteDocumentService = new QuoteDocumentService;
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
        if (! $quote) {
            return response()->json(['message' => 'Quote not found'], 200);
        }

        $data = [
            'doc_name' => $request->doc_name,
            'doc_uuid' => $request->doc_uuid,
            'document_category' => $request->bor_ref_id,
        ];
        $quoteDocumentService = new QuoteDocumentService;

        return $quoteDocumentService->deleteBorDocument($quote, $data);
    }

    public function borCompletionEmailTrigger($borRefId)
    {
        LoggerService::info('BOR Completion Email Trigger', [
            'bor_ref_id' => $borRefId,
        ]);
        $borLog = BorLog::where('bor_reference', $borRefId)->first();
        $borEmailService = new BorEmailService;
        $result = $borEmailService->sendBorCompletionEmail($borLog);
        if ($borLog->insurance_contact_id != null) {
            LoggerService::info('Sending BOR Insurer Notification', [
                'bor_ref_id' => $borRefId,
            ]);
            $isInsurerEmailSent = $borEmailService->sendBorInsurerNotification($borLog);
            if ($isInsurerEmailSent) {
                $borLog->update([
                    'email_sent' => 1,
                ]);
            }
        }

        return response()->json(['message' => 'success', 'result' => $result]);
    }

}
