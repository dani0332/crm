<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SageApiService;
use App\Http\Requests\SageRequest;
use App\Factories\SagePayloadFactory;
use Inertia\Inertia; // Import Inertia class

use Inertia\ResponseFactory;
use Illuminate\Support\Facades\Session;



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

        $message .= " SAGE Endpoint= ".$endPoint;

        return back()->with('message',$message);

       
       
    }

    private function processJsonResponse($message)
    {
        // Process the JSON response and extract message
        // ...
        
        $responseData = json_decode($message, true);
        if (isset($responseData['error'])){
            return $responseData['error']['message']['value'];
        } else {         
            return "Batch Number ". $responseData['BatchNumber'] ." created successfully";
        }
        
        return $responseData;
    }   
}
