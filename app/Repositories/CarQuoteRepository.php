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
            'claim_history' => ClaimHistoryRepository::withActive()->get()
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

    public function fetchgetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

}
