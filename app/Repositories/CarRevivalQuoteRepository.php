<?php

namespace App\Repositories;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\CarQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CarRevivalQuoteRepository extends BaseRepository
{
    public function model()
    {
        return CarQuote::class;
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        $query = CarQuote::with([
            'nationality',
            'carQuoteRequestDetail' => function($carQuoteRequestDetail){
                $carQuoteRequestDetail->with('lostReason');
            },
            'carMake',
            'uaeLicenseHeldFor',
            'uaeLicenseHeldForBackHome',
            'advisor',
            'carModel',
            'emirate',
            'carTypeInsurance',
            'claimHistory',
            'plan' => function($plan){
                $plan->with('insuranceProvider');
            },
            'paymentStatus',
            'quoteStatus',
            'vehicleType',
            'carModelDetail',
            'tier',
            'batch' => function($batches) {
                $batches->when(\Request::get('batch'), function ($query){
                    $query->whereIn('id', \Request::get('batch'));
                });
            },
            'quoteViewCount' => function($quoteViewCount){
                $quoteViewCount->where('quote_type_id', QuoteTypeId::Car);
            }
        ])->filter();
        $query->orderBy('created_at', 'desc');

        return $query->simplePaginate();
    }

    /**
     * @return mixed
     */
//    public function fetchUpdate($uuid, $data)
//    {
//        return DB::transaction(function () use ($uuid, $data) {
//            $quote = $this->byQuoteTypeId(QuoteTypes::BIKE->id())->where('uuid', $uuid)->firstOrFail();
//
//            $quoteData = Arr::only($data, [
//                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id',  'asset_value', 'currently_insured_with_id',
//            ]);
//
//            $quoteData['updated_by_id'] = Auth::user()->id;
//            $quote->update($quoteData);
//
//            $quote->bikeQuote->update(Arr::only($data, ['bike_company_to_insure', 'year_of_manufacture', 'uae_license_held_for_id']));
//
//            return $quote;
//        });
//    }



    /**
     * @return mixed
     */
//    public function fetchGetBy($column, $value)
//    {
//        $quote = $this->byQuoteTypeId(QuoteTypes::BIKE->id())
//            ->where($column, $value)
//            ->with(['bikeQuote' => function ($q) {
//                $q->with(['uaeLicenseHeldFor', 'currentlyInsuredWith']);
//            }, 'advisor', 'nationality', 'quoteDetail.lostReason', 'payments' => function ($q) {
//                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod']);
//            }, 'createdBy', 'updatedBy', 'customer.additionalContactInfo', 'documents' => function ($q) {
//                $q->with('createdBy')->orderBy('created_at', 'desc');
//            }])->firstOrFail();
//
//        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);
//
//        return $quote;
//    }


}
