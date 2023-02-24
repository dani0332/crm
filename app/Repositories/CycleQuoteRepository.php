<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CycleQuoteRepository extends BaseRepository
{
    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     *
     * @param $quoteTypeCode
     * @param $data
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::CYCLE->id()),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'cycleMake' => $data['cycle_make'],
            'cycleModel' => $data['cycle_model'],
            'accessories' => $data['accessories'],
            'hasAccident' => boolval($data['has_accident']),
            'hasGoodCondition' => boolval($data['has_good_condition']),
            'assetValue' => $data['asset_value'],
            'yearOfManufactureId' => strval($data['year_of_manufacture']),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => Auth::user()->id
        ];

        info( 'bikeQuote:' . json_encode($quoteData));
        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::CYCLE)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor'])
            ->filter()
            ->orderBy('created_at', 'desc')
            ->simplePaginate();

    }
}
