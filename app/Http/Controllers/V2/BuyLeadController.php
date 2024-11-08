<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Models\BuyLeadRequest;
use App\Services\BuyLeads\BuyLeadService;
use Illuminate\Support\Facades\Auth;

class BuyLeadController extends Controller
{
    public function __construct(public BuyLeadService $buyLeadService)
    {
        $this->middleware('permission:'.PermissionsEnum::BUY_LEADS, ['only' => ['show']]);
    }

    public function show()
    {
        $data['lobs'] = QuoteTypes::withLabels();
        $data['requests'] = BuyLeadRequest::where('user_id', Auth::id())->latest()->simplePaginate(20)->withQueryString();

        return inertia('Admin/BuyLeads/Request/Show', $data);
    }

    public function request(RequestBuyLeadsRequest $request)
    {
        return $this->buyLeadService->requestBuyLeads($request);
    }
}
