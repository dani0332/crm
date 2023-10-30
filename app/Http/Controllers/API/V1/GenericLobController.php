<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Http\Requests\OCBEmailRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\SendOCBEmailJob;
use App\Models\CarQuote;
use App\Services\CarQuoteService;
use App\Services\HealthQuoteService;
use App\Services\TravelQuoteService;

class GenericLobController extends Controller
{
    protected $carQuoteService;
    protected $healthQuoteService;
    protected $travelQuoteService;

    public function __construct(CarQuoteService $carQuoteService, HealthQuoteService $healthQuoteService, TravelQuoteService $travelQuoteService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->healthQuoteService = $healthQuoteService;
        $this->travelQuoteService = $travelQuoteService;
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

        if (isset($response['error'])) {
            vAbort($response['error']);
        }

        $pdf = $response['pdf'];

        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf->stream()), 'name' => $response['name']]);
    }

    public function getQuoteForOCBEmail(OCBEmailRequest $OCBEmailRequest)
    {
        dispatch(new SendOCBEmailJob($OCBEmailRequest->quoteUuId));

        return response()->json(['message' => 'OCB Email Job dispatched against UUID: '.$OCBEmailRequest->quoteUuId]);
    }

    public function dispatchCarRenewalEmail(string $uuid)
    {
        $lead = CarQuote::where('uuid', $uuid)->first();

        dispatch(new CarRenewalEmailJob($lead));
    }
}
