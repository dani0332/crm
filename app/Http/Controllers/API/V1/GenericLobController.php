<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Http\Requests\OCBEmailRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\SendOCBEmailJob;
use App\Models\CarQuote;
use Illuminate\Validation\ValidationException;
use App\Enums\QuoteTypes;


class GenericLobController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function exportPlansPdf($quoteType, ExportPlansPdfRequest $request)
    {
        try {

            $service = app('App\\Services\\'.ucfirst($quoteType).'QuoteService');

            if($quoteType == QuoteTypes::LIFE->value) {
                $service = app('App\\Services\\Life\\LifeQuoteService');
            }
            
            $response = $service->exportPlansPdf($request->quote_uuid, $request?->plan_ids ?? []);

            if (isset($response['error'])) {
                vAbort($response['error']);
            }

            $pdf = $response['pdf'];
           
            return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf->download()), 'name' => $response['name']]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Validation error occurred',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate PDF. '.$e->getMessage(),
                'errors' => ['error' => [$e->getMessage()]],
            ], 500);
        }
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
