<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\InslyDetail;
use App\Services\CapiRequestService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

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

        if (! empty(request()->policy_number)) {
            $query->where('policy_no', '=', request()->policy_number);
        }

        if (! empty(request()->email)) {
            $query->where('customer.email', '=', request()->email);
        }

        if (! empty(request()->mobile_no)) {
            $query->where('customer.mobile_phone', '=', request()->mobile_no);
        }
        $data = $query->simplePaginate()->withQueryString()->toArray();

        return $data;
    }

    public function fetchGetBy($column, $value)
    {
        $query = $this->where($column, $value);
        if (! empty(request()->policy_oid)) {
            $query->orWhere('policy_oid', (int) request()->policy_oid);
        }
        $policy = $query->firstOrFail();
        $data = $policy->toArray();
        if (! empty($data['installments'])) {
            $policy->premium = collect($data['installments'])->sum('gross_premium');
        }

        return $policy;
    }

    public function fetchSaveToImcrm($data)
    {
        $policyNumber = $data['policyNumber'];
        $policy = $this->where('policy_no', $policyNumber)->first();
        $email = $policy['customer']['email'] ?? null;

        // $inslyPolicyIssueDate = $policy['policy']['issue_date'] ?? null;
        // $inslyPolicyIssueDate = Carbon::parse($inslyPolicyIssueDate)->format('Y-m-d');

        $inslyPolicyIssueDate = '2020-09-21';

        // dd($inslyPolicyIssueDate);
        $appUrl = env('APP_URL');
        $inslyCoverageArray = $this->inslyInsurances();
        if (! empty($policy)) {
            $coverage = $policy['policy']['coverage'] ?? null;
            // $coverage = 'Casco';
            $quoteType = null;
            foreach ($inslyCoverageArray as $key => $item) {
                if (in_array(ucfirst($coverage), $item)) {
                    $quoteType = $key;
                }
            }

            $data = [];
            $model = $this->getModelObject($quoteType);
            if ($model) {

                $quote = $model::where('policy_number', $policyNumber)->first();
                if (! empty($quote)) {
                    $quote->link = $appUrl.'/quotes/'.strtolower($quoteType).'/'.$quote->uuid;
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
                        $route = '/api/v1-save-business-quote';
                        $quote->load('businessTypeOfInsurance', 'advisor:id,name');
                        break;
                    case QuoteTypes::CAR->value:
                        $route = '/api/v1-save-car-quote';
                        $quote->load('advisor:id,name');
                        break;
                    case QuoteTypes::LIFE->value:
                        $route = '/api/v1-save-life-quote';
                        break;
                    case QuoteTypes::HOME->value:
                        $route = '/api/v1-save-home-quote';
                        break;
                    case QuoteTypes::TRAVEL->value:
                        $route = '/api/v1-save-travel-quote';
                        break;
                    case QuoteTypes::PET->value:
                    case QuoteTypes::BIKE->value:
                    case QuoteTypes::CYCLE->value:
                    case QuoteTypes::YACHT->value:
                        $route = '/api/v1-save-personal-quote';
                        break;
                    case QuoteTypes::HEALTH->value:
                        $route = '/api/v1-save-health-quote';
                        break;
                    default:
                        $route = '';
                }

                if (! $quote->isEmpty()) {
                    foreach ($quote as $item) {
                        $item->link = $appUrl.'/quotes/'.strtolower($quoteType).'/'.$item->uuid;
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

                $dataArr = [];
                $dataArr['previousPolicyNo'] = $policy['policy_no'] ?? null;
                $insurer = $policy['policy']['insurer'] ?? null;
                if ($insurer == 'Tokio Marine Nichido') {
                    $insurer = 'Tokio Marine & Nichido Fire Insurance Co';
                }
                $insuredWith = InsuranceProviderRepository::where('code', 'like', '%'.$insurer.'%')
                    ->orWhere('text', 'like', '%'.$insurer.'%')->first();

                $dataArr['currentlyInsuredWith'] = ! empty($insuredWith) ? $insuredWith->id : null;
                $dataArr['previousPolicyStartDate'] = $policy['policy']['start_date'] ?? null;
                $dataArr['previousPolicyExpiryDate'] = $policy['policy']['end_date'] ?? null;

                $customerName = $policy['customer']['name'] ?? null;
                $arr = explode(' ', trim($customerName));
                $dataArr['firstName'] = $arr[0];
                array_shift($arr);

                $dataArr['lastName'] = implode(' ', $arr);
                $dataArr['email'] = $email;
                $dataArr['mobileNo'] = $policy['customer']['mobile_phone'] ?? '0552244556';
                $dataArr['referenceUrl'] = config('constants.APP_URL');

                $premium = null;
                $data = $policy->toArray();
                if (! empty($data['installments'])) {
                    $premium = collect($data['installments'])->sum('gross_premium');
                }

                $dataArr['premium'] = $premium;
                $dataArr['source'] = LeadSourceEnum::INSLY;

                dd($dataArr);

                info('------Insly route ------'.$route);
                info('------Insly data ------'.json_encode($dataArr));

                $response = CapiRequestService::sendCAPIRequest($route, $dataArr);

                if (! empty($response->quoteUID)) {
                    $policy->moved_to_imcrm = true;
                    $policy->imcrm_link = $appUrl.'/quotes/'.strtolower($quoteType).'/'.$response->quoteUID;
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
}
