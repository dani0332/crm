<?php

namespace App\Repositories;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\InsuranceProvider;
use App\Models\QuoteStatusLog;
use App\Traits\CentralTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CarQuoteRepository extends BaseRepository
{
    use CentralTrait;

    public function model()
    {
        return CarQuote::class;
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'carMake', 'carModel', 'insuranceProvider', 'carQuoteRequestDetail', 'car_type_insurance_id']
        )->orderBy('created_at', 'desc')->Paginate();
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

        info('fn: changeInsurer sending change insurer request for quote UUID: ' . $data['uuid'] . ' providerCode: ' . $data['provider_code'] . ' planId: ' . $data['plan_id']);

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
        $payLoad['plans'][] = (object)[
            'planId' => (int)$data['plan_id'],
            'isPayLaterActive' => true,
        ];

        return Ken::request('/save-manual-car-quote-plan', 'post', $payLoad);
    }

    /**
     * update quote status
     * @param $data
     * @return void
     */
    public function fetchUpdateQuoteStatus($data)
    {
        return DB::transaction(function () use ($data) {
            $quote = $this->where('uuid', $data['quote_uuid'])->first();

            $previousStatusId = $quote->quote_status_id;

            $quote->update(['quote_status_id' => $data['quote_status_id']]);

            QuoteStatusLog::create([
                'quote_type_id' => QuoteTypeId::Car,
                'quote_request_id' => $quote->id,
                'current_quote_status_id' => $data['quote_status_id'],
                'previous_quote_status_id' => $previousStatusId,
                'notes' => $data['notes'] ?? null
            ]);

            return $quote;
        });
    }
    
}
