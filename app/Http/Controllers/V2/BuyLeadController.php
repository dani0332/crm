<?php

namespace App\Http\Controllers\V2;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadConfigUpsertRequest;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Models\BuyLeadConfiguration;
use App\Services\BuyLeads\BuyLeadService;
use Illuminate\Support\Arr;

class BuyLeadController extends Controller
{
    public function __construct(public BuyLeadService $buyLeadService)
    {
        $this->middleware('role:'.Arr::join([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering], '|'), ['only' => ['upsertConfiguration']]);
    }

    public function upsertConfiguration(BuyLeadConfigUpsertRequest $request)
    {
        $data = $request->validated();

        $config = BuyLeadConfiguration::updateOrCreate(
            [
                'quote_type_id' => $request->getQuoteTypeId(),
                'department_id' => $data['department_id'],
            ],
            $data
        );

        return response()->json($config);
    }

    public function requestBuyLeads(RequestBuyLeadsRequest $request)
    {
        return $this->buyLeadService->requestBuyLeads($request);
    }
}
