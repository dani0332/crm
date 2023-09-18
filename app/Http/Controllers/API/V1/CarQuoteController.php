<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateLeadStatusRequest;
use App\Http\Resources\CarQuoteResource;
use App\Models\CarQuote;
use App\Repositories\CarQuoteRepository;
use App\Services\CarQuoteService;
use Illuminate\Http\Request;

class CarQuoteController extends Controller
{
    /**
     * @return void
     */
    public function index()
    {
        $quotes = CarQuoteRepository::select(
            ['id', 'code', 'uuid', 'advisor_id']
        )->filter()
        ->simplePaginate();

        //return CarQuoteResource::collection($quotes);
        return response()->json($quotes);
    }

    /**
     * get ocb details
     * @param $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOcbDetails($uuid, CarQuoteService $carQuoteService)
    {
        $ocbDetails = $carQuoteService->getOcbDetails($uuid);

        return response()->json($ocbDetails);
    }

    /**
     * @param UpdateLeadStatusRequest $request
     * @return void
     */
    public function updateQuoteStatus(UpdateLeadStatusRequest $request)
    {
        CarQuoteRepository::updateQuoteStatus($request->validated());

        return response()->json(['success' => true, 'message' => 'Lead status updated successfully']);
    }
}
