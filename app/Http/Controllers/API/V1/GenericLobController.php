<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Http\Requests\OCBEmailRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\SendOCBEmailJob;
use App\Models\CarQuote;

class GenericLobController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function exportPlansPdf($quoteType, ExportPlansPdfRequest $request)
    {
        info('Exporting Plans PDF for '.$quoteType.' Quote');

        // Call the service as usual
        $service = app('App\\Services\\'.ucfirst($quoteType).'QuoteService');
        $response = $service->exportPlansPdf($quoteType, $request->validated());

        if (isset($response['error'])) {
            vAbort($response['error']);
        }

        $pdf = $response['pdf'];
        $fileName = $response['name'];
        
        // ✅ Store the PDF in `storage/temp/` outside `app/`
        $storagePath = storage_path("temp"); // Storage path
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true); // Ensure directory exists
        }
        
        $filePath = $storagePath . DIRECTORY_SEPARATOR . $fileName;
        $pdf->save($filePath);

        return response()->json([
            'message' => 'PDF successfully generated',
            'local_path' => $filePath, // ✅ Full path to locally saved file
        ]);
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
