<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\BorStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\BorLog;
use App\Services\Bor\BorPdfService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Request;

class BorController extends Controller
{
    /**
     * @var BorPdfService
     */
    private $borPdfService;

    /**
     * @var QuoteDocumentService
     */
    private $quoteDocumentService;

    public function __construct(
        BorPdfService $borPdfService,
        QuoteDocumentService $quoteDocumentService
    ) {
        $this->borPdfService = $borPdfService;
        $this->quoteDocumentService = $quoteDocumentService;
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
            $request->merge(['quote_uuid' => $quote->code]);

            $document = $this->quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->all(), $quote);

            $borLog->update([
                'quote_document_id' => $document->id,
                'status' => BorStatusEnum::DOCUMENT_SIGNED,
                'date_signed' => now(),
            ]);

            return response()->json(['message' => 'success', 'data' => $document]);
        } catch (\Throwable $th) {
            LoggerService::error('Failed to sign document', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json(['message' => 'failed', 'error' => $th->getMessage()], 500);
        }
    }
}
