<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SageApiService;
use App\Http\Requests\SageRequest;
use App\Factories\SagePayloadFactory;

class SageApi extends Controller
{
    protected $sageApiService;
    public function __construct(SageApiService $sageApi)
    {        
        $this->sageApiService = $sageApi;
    }

    public function index()
    { }
    
    public function processSagePost(SageRequest $request)
    {
        $leadStatus = "policy booked";

        $payLoadOptions = SagePayloadFactory::createPayload($request, $leadStatus);
        $endPoint   = $payLoadOptions['endPoint'];
        $payLoad    = $payLoadOptions['payload'];

        $jsonResponse = $this->sageApiService->postToSage300($endPoint, $payLoad);

        // Process the JSON response and handle messages
        $message = $this->processJsonResponse($jsonResponse);

        echo $message; die();

        return back()->with('message', $message);
    }

    private function processJsonResponse($responseData)
    {
        // Process the JSON response and extract message
        // ...
        return $responseData;
    }   
}
