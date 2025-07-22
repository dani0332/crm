<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\BorLog;
use App\Services\Bor\BorPdfService;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;

class BorController extends Controller
{
    /**
     * @var BorPdfService
     */
    private $borPdfService;

    public function __construct(
        BorPdfService $borPdfService
    ) {
        $this->borPdfService = $borPdfService;
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
        $refId = $request->input('bor_ref_id');
        $borLog = BorLog::where('bor_reference', $refId)->first();
        $pdf = $this->borPdfService->generatePreviewBorPdf($borLog);

        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf['pdf']->download()), 'name' => $pdf['name']]);
    }
}
