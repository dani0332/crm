<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Traits\CentralTrait;

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
            ['quoteStatus', 'advisor', 'batch', 'nationality', 'uae_license_held_for_id', 'carMake', 'carModel', 'vehicleType',
                'insuranceProvider', 'claim_history_id', 'carQuoteRequestDetail', 'payment_status_id', 'tier', 'quoteViewCount',
                'car_type_insurance_id', 'updatedBy', 'carQuoteRequestDetail.lostReason',
            ])->orderBy('created_at', 'desc')->Paginate();
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
}
