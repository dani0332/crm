<?php

namespace App\Http\Controllers\API;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Events\PaymentNotifications;
use App\Factories\AllocationFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Services\ApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    use GenericQueriesAllLobs;

    private $apiService;

    public function __construct(ApiService $service)
    {
        $this->apiService = $service;
    }

    public function fetchSignupUrl(APiFetchUrl $request)
    {
        return $this->apiService->fetchSignupUrl($request);
    }

    public function sibHealthQuoteCallBack(Request $request)
    {
        if ($request->has('attributes') && isset($request['attributes']['CDBID'])) {
            return $this->apiService->sibHealthQuoteCallBack($request['attributes']['CDBID']);
        }
    }

    public function assignLeads(Request $request)
    {
        try {

            info('API assignLeads called with request params as : '.json_encode($request->all()));

            if (config('constants.DISABLE_LEAD_ALLOCATION_ENDPOINT') == 1) {
                info('------ Lead allocation ended for lead with Lead allocation endpoint disabled ------');

                return response()->json(['error' => 'Lead allocation endpoint disabled'], 503);
            }

            if ($request->has('quoteUUID') && $request->has('quoteTypeId')) {

                $allocationType = $request->input('quoteTypeId');
                $allocationId = $request->input('quoteUUID');

                info('------ Lead allocation with api started for lead : '.$allocationId.' with quote type id'.$allocationType.' ------');

                $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);

                $allocationStrategy->executeSteps();

                info('------ Lead allocation ended for lead : '.$allocationId.' ------');

                return response()->json(['message' => 'Quote allocation completed successfully!'], 200);
            } else {
                info('------ Lead allocation ended for lead with Required parameters missing ------');

                return response()->json(['error' => 'Required parameters missing'], 400);
            }
        } catch (\Exception $e) {
            info('------ Lead allocation ended for lead with An error occurred ------');

            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage(), 'stackTrace' => $e->getTraceAsString()], 500);
        }
    }

    public function quotePaymentStatusUpdated(Request $request)
    {
        $model = $this->getModelObject(strtolower($request->quoteType));
        $url = url('/');

        if (is_numeric($request->quoteId)) {
            $model = $model::find($request->quoteId);
        } else {
            $model = $model::where('uuid', $request->quoteId)->first();
        }

        if ($request->quoteType == quoteTypeCode::Business) {
            if ($model->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $url .= "/medical/amt/$model->uuid";
            } else {
                $url .= "/quotes/business/$model->uuid";
            }
        } else {
            $url .= '/quotes/'.strtolower($request->quoteType).'/'.$model->uuid;
        }

        event(new PaymentNotifications($model, $url));

        return response()->json(['message' => 'Payment notification successfully send to advisor!'], 200);
    }

}
