<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Services\CarQuoteService;
use App\Services\HealthQuoteService;

class GenericLobController extends Controller
{
    protected $carQuoteService;
    protected $healthQuoteService;

    public function __construct(CarQuoteService $carQuoteService, HealthQuoteService $healthQuoteService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->healthQuoteService = $healthQuoteService;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function exportPlansPdf($quoteType, ExportPlansPdfRequest $request)
    {
        $serviceName = strtolower($quoteType).'QuoteService';
        $response = $this->{$serviceName}->exportPlansPdf($quoteType, $request->validated());

        //return error if any
        if (isset($response['error'])) {
            vAbort($response['error']);
        }

        $pdf = $response['pdf'];

        //encode PDF as base64 and return
        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf->stream()), 'name' => $response['name']]);
    }
}
