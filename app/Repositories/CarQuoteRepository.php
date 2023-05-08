<?php

namespace App\Repositories;

use App\Facades\Ken;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class CarQuoteRepository extends BaseRepository
{
    public function model()
    {
        return CarQuote::class;
    }

    /**
     * @param $data
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

}
