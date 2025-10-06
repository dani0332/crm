<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\CustomerAcceptanceLogService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAcceptanceLogController extends Controller
{
    /**
     * Get BOR logs for a specific lead
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customerAcceptanceLogService = new CustomerAcceptanceLogService;
            [$logs, $customerAcceptanceLogsUrl] = $customerAcceptanceLogService->getCustomerAcceptanceLogs($request->only('lob', 'leadId'));

            return response()->json([
                'success' => true,
                'data' => $logs,
                'customerAcceptanceLogsUrl' => $customerAcceptanceLogsUrl,
            ]);
        } catch (Exception $th) {
            LoggerService::info('Failed to fetch Digital Consent logs', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Digital Consent logs',
            ], 500);
        }
    }

}
