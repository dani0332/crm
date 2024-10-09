<?php

namespace App\Http\Controllers;

use App\Factories\SagePayloadFactory;
use App\Services\SageApiService;
use Inertia\Inertia; // Import Inertia class

class SageApi extends Controller
{
    protected $sageApiService;
    public function __construct(SageApiService $sageApi)
    {
        $this->sageApiService = $sageApi;
    }

    public function index()
    {
    }

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
}
