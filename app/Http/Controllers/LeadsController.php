<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadsService;

class LeadsController extends ApiController
{

    /**
     * @var LeadService
     */
    private $service;

    public function __construct(LeadsService $service)
    {
        $this->service = $service;
    }


    public function index(Request $request)
    {
       return $this->respondSuccess();
    }
}
