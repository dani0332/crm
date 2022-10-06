<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Services\CarQuoteService;

class CarQuoteRequestController extends Controller
{
    /**
     * @param $quoteType
     * @param  ExportPlansPdfRequest  $request
     * @param  CarQuoteService  $carQuoteService
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function exportPlansPdf($quoteType, ExportPlansPdfRequest $request, CarQuoteService $carQuoteService)
    {
        $response = $carQuoteService->exportPlansPdf($quoteType, $request->validated());

        //return error if any
        if (isset($response['error'])) vAbort($response['error']);

        $pdf = $response['pdf'];

        //encode PDF as base64 and return
        return response()->json(['data' => 'data:application/pdf;base64,'. base64_encode($pdf->stream())]);
    }
}
