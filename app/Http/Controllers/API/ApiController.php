<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\SibFlowApi;
use App\Services\ApiService;

class ApiController extends Controller
{
    private $apiService;

    public function __construct(ApiService $service)
    {
        $this->apiService = $service;
    }

    public function fetchSignupUrl(APiFetchUrl $request)
    {
        return $this->apiService->fetchSignupUrl($request);
    }

    public function triggerSibFlow(SibFlowApi $request)
    {
        return $this->apiService->triggerSibFlow($request);
    }
}
