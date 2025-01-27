<?php

namespace App\Repositories;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Services\QuoteStatusService;
use App\Traits\CentralTrait;
use Illuminate\Support\Facades\DB;

class CarQuoteRepository extends BaseRepository
{
    use CentralTrait;

    public function model()
    {
        return CarQuote::class;
    }

    /**
     * @param  $quoteStatusId  (CarLost/Uncontactable)
     * @return mixed
     */
    public function fetchGetLostQuotes($quoteStatusId)
    {
        $query = DB::table('car_quote_request as cqr')
            ->select(DB::raw('cqr.*, clql.status as approval_status, u.email as advisor_email, clql.notes as mo_notes'))
            ->join(DB::raw('(SELECT *
             FROM   car_lost_quote_logs
                    INNER JOIN (SELECT Max(`car_lost_quote_logs`.`id`) AS `id_latest`,
                                `car_lost_quote_logs`.`car_quote_request_id` AS
                                cqr_id
                                FROM   `car_lost_quote_logs`
                                GROUP  BY `car_lost_quote_logs`.`car_quote_request_id`
                                )
                    AS `latest_log`
                    ON `latest_log`.`id_latest` = `car_lost_quote_logs`.`id`
                    AND `latest_log`.`cqr_id` =
                    `car_lost_quote_logs`.`car_quote_request_id`) clql
        '), function ($join) {
                $join->on('cqr.id', 'clql.car_quote_request_id');
            })
            ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
            ->where('cqr.quote_status_id', $quoteStatusId);

        if (! empty(request()->approval_status)) {
            $query->where('clql.status', request()->approval_status);
        }

        if (! empty(request()->advisor_id)) {
            $query->whereIn('cqr.advisor_id', request()->advisor_id);
        }

        if (! empty(request()->renewal_batch)) {
            $query->where('cqr.renewal_batch', request()->renewal_batch);
        }

        return $query->simplePaginate()->withQueryString();
    }

    /*
     * @return mixed
     */
    public function fetchGetData($paginate = true)
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'carMake', 'carModel', 'insuranceProvider', 'carQuoteRequestDetail', 'car_type_insurance_id']
        )->orderBy('created_at', 'desc')->Paginate();
    }

    public function fetchExport()
    {
        return $this->filter(false)->with(
            ['advisor', 'nationality', 'carMake', 'carModel', 'insuranceProvider', 'carQuoteRequestDetail', 'car_type_insurance_id'])->orderBy('created_at', 'desc');
    }

    /**
     * @return mixed
     */
    public function fetchChangeInsurer($data)
    {
        $provider = InsuranceProvider::where('code', $data['provider_code'])->first();

        $requestData = [
            'quoteUuid' => $data['uuid'],
            'providerId' => $provider->id,
            'planId' => $data['plan_id'],
            'userId' => strval(auth()->id()),
        ];

        info('fn: changeInsurer sending change insurer request for quote UUID: '.$data['uuid'].' providerCode: '.$data['provider_code'].' planId: '.$data['plan_id']);

        return Ken::request('/update-car-ecom-insurer', 'post', $requestData);
    }

    /**
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetAdvisors()
    {
        return UserRepository::getPersonalQuoteAdvisors(QuoteTypes::CAR->value);
    }

    public function fetchUpdateCareQuotePlanDetails($data)
    {
        $payLoad = [
            'quoteUID' => $data['quote_uuid'],
            'update' => true,
        ];
        $payLoad['plans'][] = (object) [
            'planId' => (int) $data['plan_id'],
            'isPayLaterActive' => true,
        ];

        return Ken::request('/save-manual-car-quote-plan', 'post', $payLoad);
    }

    /**
     * @return null
     */
    public function fetchFollowupStarted($data)
    {
        $quoteStatus = QuoteStatusEnum::getKey($data['quote_status_id']);
        $quoteTypeId = QuoteTypes::getIdFromValue($data['quote_type']);
        app(QuoteStatusService::class)->updateQuoteStatus($quoteTypeId, $data['quote_uuid'], $quoteStatus, [], $data['notes']);
        $quote = $this->where('uuid', $data['quote_uuid'])->first();

        // set followup id coming from kyo
        $quote->carQuoteRequestDetail->updateOrCreate(
            ['car_quote_request_id' => $quote->id],
            ['followup_id' => $data['followup_id']]
        );

        return $quote;
    }
}
