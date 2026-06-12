<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\QuoteTypes;
use App\Exceptions\CustomerNotFoundForWelcomeEmailException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportPlansPdfLinkRequest;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Http\Requests\MaWelcomEmailRequest;
use App\Http\Requests\OCBEmailRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Jobs\SendOCBEmailJob;
use App\Models\CarQuote;
use App\Services\EmailServices\CarEmailService;
use App\Services\EmailServices\TravelEmailService;
use App\Services\MyAlfredWelcomeEmailInboundService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenericLobController extends Controller
{
    /**
     * @return StreamedResponse
     *
     * @throws ValidationException
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

    public function getPlansPdfUrl($quoteType, ExportPlansPdfLinkRequest $request)
    {
        $quoteType = QuoteTypes::from(ucfirst($quoteType));
        switch ($quoteType) {
            case QuoteTypes::CAR:
                $pdfUrl = app(CarEmailService::class)->attachCarOCBPDF($request->quote_uuid);

                return response()->json(['pdf_url' => $pdfUrl]);
            case QuoteTypes::TRAVEL:
                $pdfUrl = app(TravelEmailService::class)->attachTravelOCBPDF($request->quote_uuid);

                return response()->json(['pdf_url' => $pdfUrl]);
            default:
                return response()->json(['error' => 'Invalid quote type'], 400);
        }
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
            Storage::disk('azureIMPrivate')->put($tempFilePath, $pdf->output());

            // Generate a public URL using generic method
            $publicUrl = app(QuoteDocumentService::class)->getDocumentUrl(
                $tempFilePath,
                'azureIMPrivate',
                60
            );

            if (! $publicUrl) {
                return response()->json(['error' => 'Failed to generate public URL'], 500);
            }

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

    public function sendMyAlfredWelcomeEmail(
        MaWelcomEmailRequest $request,
        MyAlfredWelcomeEmailInboundService $welcomeEmailInboundService,
    ) {
        try {
            $welcomeEmailInboundService->process(
                $request->email,
                $request->code,
                $request->source,
                $request->tag,
            );

            return response()->json(['message' => 'Welcome email job dispatched successfully'], 200);
        } catch (CustomerNotFoundForWelcomeEmailException) {
            return response()->json([
                'message' => 'Customer not found',
                'error' => 'CUSTOMER_NOT_FOUND',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to process welcome email request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
