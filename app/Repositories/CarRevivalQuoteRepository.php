<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
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
            'vehicle_types' => VehicleTypeRepository::withActive()->get(),
            'types_of_insurance' => CarTypeInsuranceRepository::withActive()->get(),
            'currently_insured_with_options' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->withActive()->get(),
            'uae_license_help_for' => UaeLicenseHeldRepository::withActive()->get(),
            'emirate_of_visa' => EmirateRepository::withActive()->get(),
            'car_make' => CarMakeRepository::active()->get(),
            'year_of_manufacture' => YearOfManufactureRepository::get(),
            'claim_history' => ClaimHistoryRepository::withActive()->get(),
        ];

        if ($isForListView) {
            $result = array_merge($result, [
                'batches' => QuoteBatchRepository::get(),
                'payment_statuses' => PaymentStatusRepository::withActive()->get(),
                'lead_statuses' => LeadStatusRepository::getList(QuoteTypeId::Car),
                'tiers' => TierRepository::withActive()->get(),
                'advisors' => UserRepository::getList(quoteTypeCode::Car_Revival),
            ]);
        }

        return $result;
    }

    public function fetchGetReportsData()
    {
        $groups = $this->where('source', '=', LeadSourceEnum::REVIVAL)->whereNotNull(['quote_batch_id', 'payment_status_id'])->with(['dtt_revival'])->get();
        $groups = $groups->groupBy('quote_batch_id');

        $data['leadConversionReport'] = $groups->map(function ($group) {
            $capture = $group->where('payment_status_id', '=', PaymentStatusEnum::CAPTURED)->where('quote_status_id', '=', QuoteStatusEnum::TransactionApproved)->count();
            $authorized = $group->where('payment_status_id', '=', PaymentStatusEnum::AUTHORISED)->where('quote_status_id', '=', QuoteStatusEnum::PaymentPending)->count();

            return [
                'quote_batch_id' => $group->first()['quote_batch_id'],
                'captured' => $capture,
                'authorized' => $authorized,
                'ratio' => $authorized > 0 ? round(($capture / $authorized) * 100, 2).'%' : null,
            ];
        })->values()
            ->all();
        foreach ($groups as $key => $value) {
            $revivalData = [];
            $revivalData['quote_batch_id'] = $key;
            $eamil_sent_count = $reply_received_count = 0;
            foreach ($value as $group) {
                if (! empty($group['dtt_revival']) && $group['dtt_revival']['email_sent'] == 1) {
                    $eamil_sent_count++;
                }

                if (! empty($group['dtt_revival']) && $group['dtt_revival']['reply_received'] == 1) {
                    $reply_received_count++;
                }
            }
            $revivalData['eamil_sent_count'] = $eamil_sent_count;
            $revivalData['reply_received_count'] = $reply_received_count;
            $revivalData['ratio'] = $eamil_sent_count > 0 ? round(($reply_received_count / $eamil_sent_count) * 100, 2).'%' : null;
            $data['emailConversionReport'][] = $revivalData;
        }

        return $data;
    }
}
