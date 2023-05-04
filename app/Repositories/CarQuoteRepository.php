<?php

namespace App\Repositories;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\CarQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CarQuoteRepository extends BaseRepository
{
    public function model()
    {
        return CarQuote::class;
    }

    /**
     * create new personal quote
     *
     * @param $quoteTypeCode
     * @return mixed
     */
//    public function fetchCreate($data)
//    {
//        $quoteData = [
//            'quoteTypeId' => intval(QuoteTypes::BIKE->id()),
//            'nationalityId' => strval($data['nationality_id']),
//            'mobileNo' => $data['mobile_no'],
//            'email' => $data['email'],
//            'firstName' => $data['first_name'],
//            'lastName' => $data['last_name'],
//            'dob' => $data['dob'],
//            'bikeCompanyToInsure' => $data['bike_company_to_insure'],
//            'assetValue' => $data['asset_value'],
//            'currentlyInsuredWithId' => strval($data['currently_insured_with_id']),
//            'uaeLicenseHeldForId' => strval($data['uae_license_held_for_id']),
//            'yearOfManufactureId' => strval($data['year_of_manufacture']),
//            'lang' => 'EN',
//            'device' => 'DESKTOP',
//            'source' => config('constants.SOURCE_NAME'),
//            'referenceUrl' => URL::current(),
//            'createdById' => Auth::user()->id,
//        ];
//
//        info('bikeQuote:'.json_encode($quoteData));
//
//        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
//    }

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
     * get all dropdown options required for form
     *
     * @return array
     */
//    public function fetchGetFormOptions()
//    {
//        return [
//            'nationalities' => NationalityRepository::withActive()->get(),
//            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
//            'yearOfManufacture' => YearOfManufactureRepository::get(),
//            'insuranceProviders' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->get(),
//        ];
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

    /**
     * @return mixed
     */
    public function fetchGetRevivalData()
    {
       return $this->model()::leftJoin('nationality', 'nationality.id', '=', 'car_quote_request.nationality_id')
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'car_quote_request.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'cqrd.lost_reason_id')
            ->leftJoin('car_make as cmake', 'cmake.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('uae_license_held_for as ulhf', 'ulhf.id', '=', 'car_quote_request.uae_license_held_for_id')
            ->leftJoin('uae_license_held_for as ulhfs', 'ulhfs.id', '=', 'car_quote_request.back_home_license_held_for_id')
            ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'car_quote_request.car_model_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'car_quote_request.emirate_of_registration_id')
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'car_quote_request.car_type_insurance_id')
            ->leftJoin('claim_history as ch', 'ch.id', '=', 'car_quote_request.claim_history_id')
            ->leftJoin('users as u', 'u.id', '=', 'car_quote_request.advisor_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'car_quote_request.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'car_quote_request.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'car_quote_request.quote_status_id')
            ->leftJoin('vehicle_type as vt', 'vt.id', '=', 'car_quote_request.vehicle_type_id')
            ->leftJoin('car_model_detail as cmd', 'cmd.id', '=', 'car_quote_request.car_model_detail_id')
            ->leftJoin('tiers as t', 't.id', '=', 'car_quote_request.tier_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'car_quote_request.quote_batch_id')
            ->leftJoin('quote_view_count as qvc', function ($join) {
                $join->on('qvc.quote_id', 'car_quote_request.id');
                $join->where('qvc.quote_type_id', QuoteTypeId::Car);
            })
           ->select(
                'car_quote_request.uuid',
                'car_quote_request.id',
                'car_quote_request.first_name',
                'car_quote_request.last_name',
                'car_quote_request.email',
                'car_quote_request.mobile_no',
                DB::raw('DATE_FORMAT(car_quote_request.dob, "%d-%m-%Y") as dob'),
                'car_quote_request.car_value',
                'car_quote_request.additional_notes',
                'car_quote_request.nationality_id',
                'car_quote_request.year_of_manufacture',
                'car_quote_request.code',
                'car_quote_request.is_ecommerce',
                'car_quote_request.premium',
                DB::raw('DATE_FORMAT(car_quote_request.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
                'car_quote_request.payment_gateway',
                'car_quote_request.source',
                DB::raw('DATE_FORMAT(car_quote_request.created_at, "%d-%m-%Y %H:%i:%s") as created_at'),
                DB::raw('DATE_FORMAT(car_quote_request.updated_at, "%d-%m-%Y %H:%i:%s") as updated_at'),
                'car_quote_request.seat_capacity',
                'car_quote_request.cylinder',
                'car_quote_request.vehicle_type_id',
                'nationality.TEXT AS nationality_id_text',
                'car_quote_request.promo_code',
                'car_quote_request.device',
                'car_quote_request.policy_number',
                'car_quote_request.previous_quote_id',
                'car_quote_request.renewal_batch',
                DB::raw('DATE_FORMAT(car_quote_request.renewal_expiry_date, "%d-%m-%Y") as renewal_expiry_date'),
                'car_quote_request.order_reference',
                'car_quote_request.payment_reference',
                'car_quote_request.calculated_value',
                'car_quote_request.created_by',
                'car_quote_request.updated_by',
                'car_quote_request.uae_license_held_for_id',
                'ulhf.TEXT AS uae_license_held_for_id_text',
                'car_quote_request.car_make_id',
                'cmake.TEXT AS car_make_id_text',
                'car_quote_request.car_model_id',
                'cmodel.TEXT AS car_model_id_text',
                'car_quote_request.emirate_of_registration_id',
                'e.TEXT AS emirate_of_registration_id_text',
                'car_quote_request.car_type_insurance_id',
                'cti.TEXT AS car_type_insurance_id_text',
                'car_quote_request.claim_history_id',
                'ch.TEXT AS claim_history_id_text',
                'car_quote_request.advisor_id',
                'u.name AS advisor_id_text',
                'car_quote_request.payment_status_id',
                'ps.text AS payment_status_id_text',
                'car_quote_request.plan_id',
                'cp.text AS plan_id_text',
                'cp.provider_id AS car_plan_provider_id',
                'cpip.text AS car_plan_provider_id_text',
                'car_quote_request.quote_status_id',
                'qs.text AS quote_status_id_text',
                'car_quote_request.year_of_manufacture AS year_of_manufacture_text',
                DB::raw('DATE_FORMAT(cqrd.next_followup_date, "%d-%m-%Y %H:%i:%s") as next_followup_date'),
                'cqrd.transapp_code',
                'cqrd.notes',
                'cqrd.lost_approval_status',
                'cqrd.lost_approval_reason',
                'vt.text as vehicle_type_id_text',
                'car_quote_request.currently_insured_with',
                'car_quote_request.currently_insured_with as currently_insured_with_text',
                'ls.text as lost_reason',
                'car_quote_request.previous_quote_policy_number',
                DB::raw('DATE_FORMAT(car_quote_request.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
                'car_quote_request.previous_quote_policy_premium',
                'car_quote_request.car_model_detail_id',
                'cmd.text as car_model_detail_id_text',
                'car_quote_request.is_modified',
                'car_quote_request.is_bank_financed',
                'car_quote_request.is_gcc_standard',
                'car_quote_request.current_insurance_status',
                'car_quote_request.year_of_first_registration',
                'car_quote_request.has_ncd_supporting_documents',
                'car_quote_request.back_home_license_held_for_id',
                'ulhfs.TEXT as back_home_license_held_for_id_text',
                DB::raw('DATE_FORMAT(car_quote_request.policy_start_date, "%d-%m-%Y") as policy_start_date'),
                DB::raw('DATE_FORMAT(car_quote_request.policy_issuance_date, "%d-%m-%Y") as policy_issuance_date'),
                'car_quote_request.customer_id',
                'car_quote_request.parent_duplicate_quote_id',
                'car_quote_request.renewal_import_code',
                'car_quote_request.quote_link',
                DB::raw('DATE_FORMAT(cqrd.advisor_assigned_date, "%d-%m-%Y %H:%i:%s") as advisor_assigned_date'),
                DB::raw("DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),dob)), '%Y') + 0 AS customer_age"),
                'car_quote_request.tier_id',
                't.name as tier_id_text',
                'qvc.visit_count as visit_count',
                't.cost_per_lead as cost_per_lead',
                'car_quote_request.quote_batch_id',
                'qb.name as quote_batch_id_text',
                'car_quote_request.car_value_tier'
            )
//            ->filter()
            ->simplePaginate();

    }
}
