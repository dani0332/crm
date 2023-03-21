<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class YachtQuoteRepository extends BaseRepository
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
            'quoteTypeId' => intval(QuoteTypes::YACHT->id()),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'boatDetails' => $data['boat_details'],
            'engineDetails' => $data['engine_details'],
            'claimExperience' => $data['claim_experience'],
            'use' => $data['use'],
            'operatorExperience' => $data['operator_experience'],
            'assetValue' => $data['asset_value'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => Auth::user()->id,
        ];

        info('YachtQuote create data : '.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @param $uuid
     * @param $data
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::YACHT->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, ['first_name', 'last_name', 'email', 'mobile_no']);
            $quoteData['updated_by_id'] = Auth::user()->id;

            $quote->update($quoteData);

            $quote->yachtQuote->update(Arr::only($data, ['boat_details', 'engine_details', 'claim_experience', 'use', 'operator_experience']));

            return $quote;
        });
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->byQuoteTypeId(QuoteTypes::YACHT->id())
            ->where($column, $value)
            ->with(['yachtQuote', 'advisor', 'nationality', 'quoteDetail.lostReason', 'payments' => function ($q) {
                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod']);
            }, 'createdBy', 'updatedBy', 'documents' => function($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            }])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::YACHT)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor'])
            ->filter()
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
    }
}
