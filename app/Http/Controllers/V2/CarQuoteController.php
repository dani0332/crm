<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeInsurerRequest;
use App\Http\Requests\UpdateCarQuotePlanDetailsRequest;
use App\Repositories\CarQuoteRepository;
use Illuminate\Http\Request;

class CarQuoteController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeInsurer(ChangeInsurerRequest $request)
    {
        $response = CarQuoteRepository::changeInsurer($request->validated());

        return response()->json($response);
    }

    public function updateCarPlanDetails(UpdateCarQuotePlanDetailsRequest $request)
    {
        $response = CarQuoteRepository::updateCareQuotePlanDetails($request->validated());

        return response()->json($response);
    }
}
