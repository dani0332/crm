<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfLinkRequest;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Http\Requests\OCBEmailRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Jobs\SendOCBEmailJob;
use App\Models\CarQuote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

            $response = $this->getExportPdf($quoteType, $request);

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
                'errors' => ['error' => [$e->getMessage()],
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ],
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

    public function exportPlansPdfLink($quoteType, ExportPlansPdfLinkRequest $request)
    {
        try {

            $response = $this->getExportPdf($quoteType, $request);

            if (isset($response['error'])) {
                vAbort($response['error']);
            }

            $pdf = $response['pdf'];

            // Generate a unique temporary file path
            $tempFilePath = 'temp/'.uniqid().'.pdf';
            Storage::disk('azureIM')->put($tempFilePath, $pdf->output());

            // Generate a public URL
            $publicUrl = Storage::disk('azureIM')->temporaryUrl(
                $tempFilePath,
                now()->addMinutes(60)
            );

            // Use a job to handle file deletion
            DeleteTempOCBPDFFileJob::dispatch($tempFilePath)->delay(now()->addMinutes(60));

            return response()->json(['publicUrl' => $publicUrl, 'name' => $response['name']]);
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

    private function getExportPdf($quoteType, $request)
    {
        $service = app('App\\Services\\'.ucfirst($quoteType).'QuoteService');

        if ($quoteType == strtolower(QuoteTypes::LIFE->value)) {
            $service = app('App\\Services\\Life\\LifeQuoteService');
        }

        return $service->exportPlansPdf($quoteType, $request->validated());
    }

}
