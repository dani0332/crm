<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ApiService;
use App\Http\Requests\APiFetchUrl;

class ApiController extends Controller
{
    private $apiService;
    public function __construct(ApiService $service)
    {
        $this->apiService = $service;
    }

    public function fetchSignupUrl(APiFetchUrl $request) {
        return $this->apiService->fetchSignupUrl($request);
    }
}
