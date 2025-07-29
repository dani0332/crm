<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FtcEmailLogRequestStore;
use App\Services\FtcEmailLogService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FtcEmailLogController extends Controller
{
    use GenericQueriesAllLobs;

    protected $ftcEmailLogService;

    public function __construct(FtcEmailLogService $ftcEmailLogService)
    {
        $this->ftcEmailLogService = $ftcEmailLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $quoteType = $request->quoteTrackableType;
        $quoteObject = $this->getQuoteObject($quoteType, $request->quoteTrackableId);
        $emailLogs = $quoteObject->ftcEmailLogs()->get();

        return response()->json($emailLogs);
    }

    public function store($quoteType, $quoteUuid, FtcEmailLogRequestStore $request)
    {
        try {
            LoggerService::info('Creating FTC email log', '', ['feature' => 'ftc_email_log']);
            if ($quote = $this->getQuoteObject($quoteType, $quoteUuid)) {
                $payload = [
                    'email' => $request->email,
                    'subject' => $request->subject,
                    'status' => strtolower($request->status),
                    'link' => $request->link,
                    'uuid' => $request->uuid ?? generateUUID(),
                    'quote_trackable_id' => $quote->id,
                    'quote_trackable_type' => get_class($quote),
                ];
                $ftcEmailLog = $this->ftcEmailLogService->createTrackEmail($payload);

                return response()->json($ftcEmailLog, Response::HTTP_OK);
            }

            return response()->json(['message' => 'Quote not found.'], 404);
        } catch (\Exception $th) {
            LoggerService::error('Error creating FTC email log', [
                'request' => $request->all(),
            ], $th, ['ref_id' => $quoteUuid, 'feature' => 'ftc_email_log']);

            return response()->json(['message' => 'Internal server error.'], 500);
        }

    }

    public function update(Request $request)
    {
        try {
            LoggerService::info('Updating FTC email log', '', ['feature' => 'ftc_email_log']);
            $payload = [
                'status' => strtolower($request->status),
            ];
            $trackEmail = $this->ftcEmailLogService->updateTrackEmail($payload, '', $request->uuid);
            if ($trackEmail == null) {
                return response()->json(['message' => 'Email log not found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['message' => 'Email log updated successfully.', 'data' => $trackEmail], Response::HTTP_OK);
        } catch (\Exception $th) {
            LoggerService::error('Error updating FTC email log', [
                'request' => $request->all(),
            ], $th, ['feature' => 'ftc_email_log']);

            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }
}
