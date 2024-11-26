<?php

namespace App\Http\Controllers;

use App\Enums\SageEnum;
use App\Factories\SagePayloadFactory;
use App\Models\SageApiLog;
use App\Services\SageApiService;
use Illuminate\Http\Request;

class SageApi extends Controller
{
    protected $sageApiService;

    public function __construct(SageApiService $sageApi)
    {
        $this->sageApiService = $sageApi;
    }

    public function index() {}

    private function processRequest($request, $leadStatus)
    {
        $payLoadOptions = SagePayloadFactory::createPayload($request, $leadStatus);
        $endPoint = $payLoadOptions['endPoint'];
        $payLoad = $payLoadOptions['payload'];
        $jsonResponse = $this->sageApiService->postToSage300($endPoint, $payLoad);
        // Process the JSON response and handle messages
        $message = $this->processJsonResponse($jsonResponse);
        $message .= ' SAGE Endpoint= '.$endPoint;

        return $message;
    }

    private function processJsonResponse($message)
    {
        // Process the JSON response and extract message
        $responseData = json_decode($message, true);
        if (isset($responseData['error'])) {
            return $responseData['error']['message']['value'];
        } else {
            return 'Batch Number '.$responseData['BatchNumber'].' created successfully';
        }

        return $responseData;
    }
    public function sageApiLogs(Request $request, $sectionId)
    {
        $sageApiLogs = SageApiLog::with('user')->where(['section_type' => $request->modelClass, 'section_id' => $sectionId])->get();

        return response()->json(['success' => true, 'sageApiLogs' => $sageApiLogs]);
    }

    public function getLastSageError(Request $request, $sectionId)
    {
        $latestSageErrorResponse = SageApiLog::where(['section_type' => $request->modelClass, 'section_id' => $sectionId, 'status' => SageEnum::STATUS_FAIL])->latest()->first()?->response;
        if ($latestSageErrorResponse) {
            $latestSageErrorResponse = json_decode($latestSageErrorResponse, true);
        }

        return response()->json(['success' => true, 'error' => $latestSageErrorResponse['error']['message']['value'] ?? null]);
    }
}
