<?php

namespace App\Repositories;

use App\Models\CarQuote;
use App\Traits\CentralTrait;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;

class CarQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return CarQuote::class;
    }

    public function fetchGetBy($column, $value)
    {
        $quote = CarQuote::with([
            'nationality',
            'carQuoteRequestDetail' => function($carQuoteRequestDetail){
                $carQuoteRequestDetail->with('lostReason');
            },
            'carMake',
            'uaeLicenseHeldFor',
            'carModel',
            'emirate',
            'carTypeInsurance',
            'claimHistory',
            'advisor',
            'payments' => function($payments){
                $payments->with('paymentStatus', 'paymentMethod');
            },
            'documents' => function($documents){
                $documents->with('createdBy')->orderBy('created_at', 'DESC');
            },
            'vehicleType',
            'carModelDetail',
            'batch',
            'tier',
            'createdBy',
            'updatedBy',
            'customer' => function($customer){
                $customer->with('additionalContactInfo');
            },
        ])
        ->where([
            $column => $value,
            'source' => LeadSourceEnum::REVIVAL
        ])->firstOrFail();

        return $quote;
    }

    /**
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetFormOptions($is_for_listview = true)
    {
        $result = [
            'vehicle_types' => VehicleTypeRepository::withActive()->get(),
            'types_of_insurance' => CarTypeInsuranceRepository::withActive()->get(),
            'currently_insured_with_options' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->withActive()->get(),
            'nationalities' => NationalityRepository::withActive()->get(),
            'uae_license_help_for' => UaeLicenseHeldRepository::withActive()->get(),
            'emirate_of_visa' => EmirateRepository::withActive()->get(),
            'car_make' => CarMakeRepository::active()->get(),
            'year_of_manufacture' => YearOfManufactureRepository::get(),
        ];

        if($is_for_listview){
            $result = array_merge($result, [
                'batches' => QuoteBatchRepository::get(),
                'payment_statuses' => PaymentStatusRepository::withActive()->get(),
                'lead_statuses' => LeadStatusRepository::getList(QuoteTypeId::Car),
                'tiers' => TierRepository::withActive()->get(),
                'advisors' => AdvisorRepository::getList(quoteTypeCode::Car_Revival),
            ]);
        }

        return $result;
    }

    public function fetchEcomDetails()
    {
//        $response['providerName'] = '';
//        $response['network'] = '';
//        $response['paymentStatus'] = '';
//        $response['paidAt'] = '';
//        $response['planName'] = '';
//
//        $planData = HealthQuotePlan::where('health_quote_request_id', $data->id)->first();
//        if ($planData) {
//            $planPayload = json_decode($planData->plan_payload, true);
//            if (isset($planPayload['plans'])) {
//                foreach ($planPayload['plans'] as $plan) {
//                    if ($plan['id'] == $data->plan_id) {
//                        $response['providerName'] = $plan['providerName'];
//                        $response['paymentStatus'] = GenericRequestEnum::NotApplicable;
//                        $response['paidAt'] = GenericRequestEnum::NotApplicable;
//                        $response['planName'] = $plan['name'];
//                        if (isset($plan['benefits'], $plan['benefits']['feature'])) {
//                            foreach ($plan['benefits']['feature'] as $value) {
//                                if ($value['code'] == GenericRequestEnum::TPA_Code) {
//                                    $response['network'] = $value['value'];
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        return $response;
    }

}
