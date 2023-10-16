<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\InslyDetail;
use App\Models\QuoteType;
use App\Services\CapiRequestService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MongoDB\Operation\Update;

class InslyDetailRepository extends BaseRepository
{
    use GenericQueriesAllLobs;
    public function model()
    {
        return InslyDetail::class;
    }
    public function fetchGetData()
    {
        $query = InslyDetail::query();

        if (!empty(request()->policy_number)) {
            $query->where('policy_no', '=', request()->policy_number);
        }

        if (!empty(request()->email)) {
            $query->where('customer.email', '=', request()->email);
        }

        if (!empty(request()->mobile_no)) {
            $query->where('customer.mobile_phone', '=', request()->mobile_no);
        }
        $data = $query->simplePaginate()->withQueryString()->toArray();

        return $data;
    }

    public function fetchGetBy($column, $value)
    {
        $policy = $this->where($column, $value)->firstOrFail();
        $data = $policy->toArray();
        $policy->quoteType = $this->getQuoteType($data['policy']['coverage']);

        if (!empty($data['installments'])) {
            $policy->premium = collect($data['installments'])->sum('gross_premium');
        }
        return $policy;
    }

    private function getQuoteType($coverage)
    {
        $coverage = $coverage ?? null;
        $inslyCoverageArray = $this->inslyInsurances();
        $quoteType = null;
        foreach ($inslyCoverageArray as $key => $item) {
            if (in_array(ucfirst($coverage), $item)) {
                $quoteType = $key;
            }
        }
        return $quoteType;
    }

    public function fetchSaveToImcrm($data)
    {
        $policyNumber   = $data['policyNumber'];
        $validateAll    = $data['validateAll'];

        $policy = $this->where('policy_no', $policyNumber)->first();

        $email = $policy['customer']['email'] ?? null;
        $phpDateTime = $policy['policy']['issue_date']->toDateTime();
        $inslyPolicyIssueDate = $phpDateTime->format('Y-m-d');
        $appUrl = env('APP_URL');

        if (!empty($policy)) {
            $coverage = $policy['policy']['coverage'];
            $quoteType = $this->getQuoteType($coverage);

            $data = [];
            $model = $this->getModelObject($quoteType);
            if ($model) {

                $quote = $model::where('policy_number', $policyNumber)->first();

                if (!empty($quote) && $validateAll) {
                    if (in_array($quoteType, [quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::Jetski])) {
                        $quote->link = $appUrl . '/personal-quotes/' . strtolower($quoteType) . '/' . $quote->uuid;
                    } else {
                        $quote->link = $appUrl . '/quotes/' . strtolower($quoteType) . '/' . $quote->uuid;
                    }
                    $quote->link = $appUrl . '/quotes/' . strtolower($quoteType) . '/' . $quote->uuid;
                    $quote->modelType = $quoteType;
                    $data[] = $quote;

                    return [
                        'status' => 200,
                        'message' => '',
                        'type' => 'policy_number',
                        'data' => $data,
                    ];
                }

                $dateFrom = Carbon::createFromFormat('Y-m-d', $inslyPolicyIssueDate)->addMonths(-1)->startOfDay();
                $dateTo = Carbon::createFromFormat('Y-m-d', $inslyPolicyIssueDate)->addMonths(1)->endOfDay();

                $quote = $model::whereHas('payments', function ($query) use ($dateFrom, $dateTo) {
                    return $query->whereBetween('captured_at', [$dateFrom, $dateTo]);
                })->where('email', $email)->get();

                switch (ucfirst($quoteType)) {

                    case QuoteTypes::BUSINESS->value:
                        $quote->load('businessTypeOfInsurance', 'advisor:id,name');
                        break;
                    case QuoteTypes::CAR->value:
                        $quote->load('advisor:id,name');
                        break;
                        // case QuoteTypes::LIFE->value:
                        //     break;
                        // case QuoteTypes::HOME->value:
                        //     break;
                        // case QuoteTypes::TRAVEL->value:
                        //     break;
                        // case QuoteTypes::PET->value:
                        // case QuoteTypes::BIKE->value:
                        // case QuoteTypes::CYCLE->value:
                        // case QuoteTypes::YACHT->value:
                        //     break;
                        // case QuoteTypes::HEALTH->value:
                        //     break;
                        // default:
                        //     $route = '';
                }


                if (!$quote->isEmpty() && $validateAll) {
                    foreach ($quote as $item) {
                        if (in_array($quoteType, [quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::Jetski])) {
                            $item->link = $appUrl . '/personal-quotes/' . strtolower($quoteType) . '/' . $item->uuid;
                        } else {
                            $item->link = $appUrl . '/quotes/' . strtolower($quoteType) . '/' . $item->uuid;
                        }
                        $item->modelType = $quoteType;
                        $data[] = $item;
                    }

                    return [
                        'status' => 200,
                        'message' => '',
                        'type' => 'email',
                        'data' => $data,
                    ];
                }

                $payLoad = $this->prePareData($policy, $quoteType);

                info('InslyLead - Payload: ' . json_encode($payLoad));
                $id = $model::create($payLoad)->id;
                info('InslyLead - created Lead Id : ' . json_encode($id));
                if (!empty($id)) {
                    $obj = $model::where('id', $id)->first();
                    switch (ucfirst($quoteType)) {

                        case QuoteTypes::BUSINESS->value:
                            $obj->businessQuoteRequestDetail()->create(['insly_id' => $policy->_id]);
                            break;

                        case QuoteTypes::CAR->value:
                            $obj->carQuoteRequestDetail()->create(['insly_id' => $policy->_id]);
                            break;

                        case QuoteTypes::LIFE->value:
                            $obj->lifeQuoteRequestDetail()->create(['insly_id' => $policy->_id]);
                            break;

                        case QuoteTypes::HOME->value:
                            $obj->homeQuoteRequestDetail()->create(['insly_id' => $policy->_id]);
                            break;

                        case QuoteTypes::TRAVEL->value:
                            $obj->travelQuoteRequestDetail()->create(['insly_id' => $policy->_id]);
                            break;

                        case QuoteTypes::HEALTH->value:
                            $obj->healthQuoteRequestDetail()->create(['insly_id' => $policy->_id]);
                            break;
                        case QuoteTypes::PET->value:
                        case QuoteTypes::BIKE->value:
                        case QuoteTypes::CYCLE->value:
                        case QuoteTypes::YACHT->value:
                            $obj->quoteDetail()->create(['insly_id' => $policy->_id]);
                            break;
                    }
                    $policy->moved_to_imcrm = true;
                    if (in_array($quoteType, [quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::Jetski])) {
                        $policy->imcrm_link = $appUrl . '/personal-quotes/' . strtolower($quoteType) . '/' . $obj->uuid;
                    } else {
                        $policy->imcrm_link = $appUrl . '/quotes/' . strtolower($quoteType) . '/' . $obj->uuid;
                    }
                    $policy->moved_to_imcrm_date = date('Y-m-d H:i:s');
                    $policy->moved_to_imcrm_by = auth()->user()->name;
                    $policy->save();
                }
                $data[] = $this->where('policy_no', $policyNumber)->first()->toArray();

                return [
                    'status' => 201,
                    'message' => 'Lead Created Successully',
                    'data' => $data,
                ];
            }
        }
    }



    // payload
    private function prePareData($policy, $quoteType)
    {

        $dataArr = [];
        $dataArr['previous_quote_policy_number'] = $policy['policy_no'] ?? null;
        $dataArr['email'] = $policy['customer']['email'] ?? null;
        $insurer = $policy['policy']['insurer'] ?? null;
        if ($insurer == 'Tokio Marine Nichido') {
            $insurer = 'Tokio Marine & Nichido Fire Insurance Co';
        }
        $insuredWith = InsuranceProviderRepository::where('code', 'like', '%' . $insurer . '%')
            ->orWhere('text', 'like', '%' . $insurer . '%')->first();

        // $dataArr['currently_insured_with'] = !empty($insuredWith) ? $insuredWith->id : null;
        // $dataArr['previousPolicyStartDate'] = $policy['policy']['start_date']->toDateTime()->format('Y-m-d') ?? null;
        $dataArr['previous_policy_expiry_date'] = $policy['policy']['end_date']->toDateTime()->format('Y-m-d') ?? null;

        $customerName = $policy['customer']['name'] ?? null;
        $arr = explode(' ', trim($customerName));
        $dataArr['first_name'] = $arr[0];
        array_shift($arr);

        $dataArr['last_name'] = implode(' ', $arr);
        $dataArr['mobile_no'] = $policy['customer']['mobile_phone'] ?? '0552244556';

        $premium = null;
        $data = $policy->toArray();
        if (!empty($data['installments'])) {
            $premium = collect($data['installments'])->sum('gross_premium');
        }
        $quoteTypeData = QuoteType::where('code', $quoteType)->first();
        $capi = new CapiRequestService();
        $resp = $capi->getUUID($quoteTypeData->id);
        if ($resp) {
            $dataArr['uuid'] = $resp->uuid;
            $dataArr['code'] = $quoteTypeData->short_code . '-' . $resp->uuid;
        }
        $dataArr['premium'] = $premium;
        $dataArr['source'] = LeadSourceEnum::INSLY;
        if (in_array($quoteType, [quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::Jetski])) {
            $dataArr['quote_type_id'] = $quoteTypeData->id;
            $dataArr['is_ecommerce'] = false;
        }

        return $dataArr;
    }
}
