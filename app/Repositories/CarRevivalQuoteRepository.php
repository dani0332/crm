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
            'batch',
            'quoteViewCount' => function($quoteViewCount){
                $quoteViewCount->where('quote_type_id', QuoteTypeId::Car);
            }
        ])->filter();
        // Custom Filters
        $query->when(\Request::get('batch'), function ($query){
            $query->whereHas('batch', function ($batch){
                $batch->whereIn('id', \Request::get('batch'));
            });
        });
        $query->when(\Request::get('quote_status'), function ($query){
            $query->whereHas('quoteStatus', function ($quoteStatus){
                $quoteStatus->whereIn('id', \Request::get('quote_status'));
            });
        });
        $query->when(\Request::get('tier'), function ($query){
            $query->whereIn('tier_id', \Request::get('tier'));
        });
        $query->when(\Request::get('viehicle_type'), function ($query){
            $query->where('vehicle_type_id', \Request::get('viehicle_type'));
        });
        $query->when(\Request::get('car_type_insurance'), function ($query){
            $query->where('car_type_insurance_id', \Request::get('car_type_insurance'));
        });
        $query->when(\Request::get('currently_insured_with'), function ($query){
            $query->whereHas('plan.insuranceProvider', function ($currentlyInsuredWith){
                $currentlyInsuredWith->where('provider_id', \Request::get('currently_insured_with'));
            });
        });
        $query->when(\Request::get('advisors'), function ($query){
            $query->whereHas('advisor', function ($currentlyInsuredWith){
                $currentlyInsuredWith->where('advisor_id', \Request::get('advisors'));
            });
        });
        $query->when(\Request::get('advisor_date_start'), function ($query){
            $query->whereHas('carQuoteRequestDetail', function ($advisorAssignDate){
                if (isset(request()->advisor_date_start) && isset(request()->advisor_date_end)) {
                    $startDate = date('Y-m-d 00:00:00', strtotime(request()->advisor_date_start));
                    $endDate = date('Y-m-d 23:59:59', strtotime(request()->advisor_date_end));
                    $advisorAssignDate->whereBetween(DB::raw('date(advisor_assigned_date)'), [$startDate, $endDate]);
                }
            });
        });
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
