<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadsService;
use App\Transformers\LeadsTransformer;

class LeadsController extends ApiController
{

    /**
     * @var LeadService
     */
    private $service;

    public function __construct(LeadsService $service)
    {
        $this->service = $service;
        //$this->transformer = ;
    }

    public function index(Request $request)
    {
        try {
            $filters = ['coupon_code' => 'fsd', 'is_active' => 1, 'discount' => 0];
            $response = $this->service->getLeadListWithFilter($filters);
            return $this->respondData($response);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }
}
