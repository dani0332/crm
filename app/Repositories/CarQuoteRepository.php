<?php

namespace App\Repositories;

use App\Enums\LookupsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
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
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'batches' => QuoteBatchRepository::get(),
            'payment_statuses' => PaymentStatusRepository::withActive()->get(),
            'lead_statuses' => LeadStatusRepository::getList(QuoteTypeId::Car),
            'tiers' => TierRepository::withActive()->get(),
            'vehicle_types' => VehicleTypeRepository::withActive()->get(),
            'types_of_insurance' => CarTypeInsuranceRepository::withActive()->get(),
            'currently_insured_with_options' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->withActive()->get(),
            'advisors' => AdvisorRepository::getList(quoteTypeCode::Car)
        ];
    }

}
