<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\CarMake;
use App\Models\CarQuote;
use App\Models\CarTypeInsurance;
use App\Models\ClaimHistory;
use App\Models\DttRevival;
use App\Models\Emirate;
use App\Models\PaymentStatus;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Models\Tier;
use App\Models\UAELicenseHeldFor;
use App\Models\VehicleType;
use App\Models\YearOfManufacture;
use Illuminate\Support\Facades\DB;

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
            'carQuoteRequestDetail' => function ($carQuoteRequestDetail) {
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
            'plan' => function ($plan) {
                $plan->with('insuranceProvider');
            },
            'paymentStatus',
            'quoteStatus',
            'vehicleType',
            'carModelDetail',
            'tier',
            'batch',
            'quoteViewCount' => function ($quoteViewCount) {
                $quoteViewCount->where('quote_type_id', QuoteTypeId::Car);
            },
        ])
            ->where('source', LeadSourceEnum::REVIVAL)
            ->filter();
        // Custom Filters
        $query->when(request()->get('quote_batch_id'), function ($query) {
            $query->whereHas('batch', function ($batch) {
                $batch->whereIn('id', request()->get('quote_batch_id'));
            });
        });
        $query->when(request()->get('currently_insured_with'), function ($query) {
            $query->whereHas('plan.insuranceProvider', function ($currentlyInsuredWith) {
                $currentlyInsuredWith->where('provider_id', request()->get('currently_insured_with'));
            });
        });
        $query->when(request()->get('advisor_date_start'), function ($query) {
            $query->whereHas('carQuoteRequestDetail', function ($advisorAssignDate) {
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

    public function fetchGetBy($column, $value)
    {
        $quote = CarQuote::with([
            'nationality',
            'carQuoteRequestDetail' => function ($carQuoteRequestDetail) {
                $carQuoteRequestDetail->with('lostReason');
            },
            'carMake',
            'uaeLicenseHeldFor',
            'carModel',
            'emirate',
            'carTypeInsurance',
            'claimHistory',
            'advisor',
            'payments' => function ($payments) {
                $payments->with('paymentStatus', 'paymentMethod');
            },
            'documents' => function ($documents) {
                $documents->with('createdBy')->orderBy('created_at', 'DESC');
            },
            'vehicleType',
            'carModelDetail',
            'batch',
            'tier',
            'createdBy',
            'updatedBy',
            'customer' => function ($customer) {
                $customer->with('additionalContactInfo');
            },
        ])
            ->where([
                $column => $value,
                'source' => LeadSourceEnum::REVIVAL,
            ])->firstOrFail();

        return $quote;
    }

    /**
     * get all dropdown options required for form.
     *
     * @return array
     */
    public function fetchGetFormOptions($isForListView = true)
    {
        $result = [
            'nationalities' => NationalityRepository::withActive()->get(),
            'vehicle_types' => VehicleType::withActive()->get(),
            'types_of_insurance' => CarTypeInsurance::withActive()->get(),
            'currently_insured_with_options' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->withActive()->get(),
            'uae_license_help_for' => UAELicenseHeldFor::withActive()->get(),
            'emirate_of_visa' => Emirate::withActive()->get(),
            'car_make' => CarMake::active()->get(),
            'year_of_manufacture' => YearOfManufacture::get(),
            'claim_history' => ClaimHistory::withActive()->get(),
        ];

        if ($isForListView) {
            $result = array_merge($result, [
                'batches' => QuoteBatches::get(),
                'payment_statuses' => PaymentStatus::withActive()->get(),
                'lead_statuses' => QuoteStatus::whereHas('quoteStatusMap', function ($q) {
                    $q->where('quote_type_id', '=', QuoteTypeId::Car);
                })
                    ->withActive()
                    ->get(),
                'tiers' => Tier::active()->get(),
                'advisors' => UserRepository::getList(quoteTypeCode::Car_Revival),
            ]);
        }

        return $result;
    }

    public function fetchupdateQuote(CarQuote $lead)
    {
        $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
        DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
        info('UpdateLeadSource  - UUID - '.$lead->uuid.' - source updated to Revival');
    }

}
