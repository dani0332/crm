<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Models\BorLog;
use App\Models\DocumentType;
use App\Services\Bor\BorEmailService;
use App\Services\Bor\BorPdfService;
use App\Services\Bor\BorService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BorController extends Controller
{
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
            $request->merge(['document_category' => $request->input('bor_ref_id')]);
            $previousDoc = $borLog->document;

            if($previousDoc && $previousDoc->doc_url) {
                Storage::disk('azureIM')->delete($previousDoc->doc_url);
                $previousDoc->delete();
            }

            $document = $this->quoteDocumentService->uploadQuoteDocument(data_get($request, 'is_base_64', 0) == 1 ? $request->file : $request->file('file'), $request->all(), $quote);

            $borLog->update([
                'quote_document_id' => $document->id,
                'document_id' => $document->doc_uuid,
                'status' => BorStatusEnum::DOCUMENT_SIGNED,
                'date_signed' => now(),
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
            $borDocTypes = [$this->borService->determineBorDocumentType($quoteType->value)];
        } else {
            $borDocTypes = [DocumentTypeCode::BAL_BIKE, DocumentTypeCode::BAL, DocumentTypeCode::BAL_HOME, DocumentTypeCode::BAL_LIFE, DocumentTypeCode::BAL_TRVL, DocumentTypeCode::BAL_HLTH, DocumentTypeCode::BAL_YACHT, DocumentTypeCode::BAL_CYCLE, DocumentTypeCode::BAL_PET, DocumentTypeCode::BAL_BS];
        }
        $documentTypes = DocumentType::whereIn('code', $borDocTypes)
            ->when(($request->has('quote_type_id') && $request->input('quote_type_id') == QuoteTypeId::Car), function ($query) use ($request) {
                $query->where('quote_type_id', $request->input('quote_type_id'));
            })
            ->where('is_active', 1)
            ->whereNull('business_type_of_insurance_id')
            ->get();
        return response()->json(['data' => $documentTypes]);
    }

    public function borCompletionEmailTrigger($borRefId)
    {
        $borLog = BorLog::where('bor_reference', $borRefId)->first();
        $result = $this->borEmailService->sendBorCompletionEmail($borLog);
        return response()->json(['message' => 'success', 'result' => $result]);
    }
}
