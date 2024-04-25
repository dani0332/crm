<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\QuoteBatches;
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
                'tiers' => TierRepository::active()->get(),
                'advisors' => UserRepository::getList(quoteTypeCode::Car_Revival),
            ]);
        }

        return $result;
    }

    public function fetchupdateQuote($data)
    {
        $inbound = new \Postmark\Inbound(file_get_contents('php://input'));

        $subject = $inbound->Subject();
        $strings = explode('-', $subject);
        if (!empty($strings[1])) {
            $uuid = $strings[1];
            $this->where('uuid', $uuid)->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            DttRevival::where('uuid', $uuid)->update(['reply_received' => 1]);

            info('Lead  - UUID - ' . $uuid . ' - source updated to Revival');
        }
    }
    public function fetchGetReportsData($request)
    {
        $source = [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID];

        $carInsurancetypeId = $request->car_type_insurance_id;
        $leadSource = $request->lead_source;

        $query = $this
            ->select(
                'quote_batch_id',
                DB::raw('COUNT(CASE  WHEN payment_status_id = ' . PaymentStatusEnum::CAPTURED . ' THEN 1 ELSE NULL END) as conversion_captured'),
                DB::raw('COUNT(CASE  WHEN source = "' . LeadSourceEnum::REVIVAL . '" THEN 1 ELSE NULL END) as total_revived'),
                DB::raw('COUNT(CASE  WHEN payment_status_id = ' . PaymentStatusEnum::CAPTURED . ' and  quote_status_id = ' . QuoteStatusEnum::TransactionApproved . ' THEN 1 ELSE NULL END) as captured'),
                DB::raw('COUNT(CASE  WHEN payment_status_id = ' . PaymentStatusEnum::AUTHORISED . ' and  quote_status_id = ' . QuoteStatusEnum::PaymentPending . ' THEN 1 ELSE NULL END) as authorized'),
                DB::raw('COUNT(CASE  WHEN email_sent = 1 THEN 1 ELSE NULL END) as email_sent_count'),
                DB::raw('COUNT(CASE  WHEN reply_received = 1 THEN 1 ELSE NULL END) as reply_received_count'),
            )
            ->leftjoin('dtt_revivals', 'dtt_revivals.quote_id', 'car_quote_request.id')
            ->whereNotNull(['quote_batch_id', 'payment_status_id'])
            ->orderBy('quote_batch_id','desc');

        if (!empty($leadSource)) {
            $query->where('source', $leadSource);
        } else {
            $query->whereIn('source', $source);
        }
        if (!empty($carInsurancetypeId)) {
            $query->where('car_type_insurance_id', $carInsurancetypeId);
        }
        $record = $query->groupBy('quote_batch_id')->get()->toArray();

        $data = [];
        foreach ($record as $item) {
            $batch = QuoteBatches::find($item['quote_batch_id'])->name;
            $c['quote_batch_id'] = $batch;
            $c['conversion_captured'] = $item['conversion_captured'];
            $c['total_revived'] = $item['total_revived'];
            $c['ratio'] = $item['conversion_captured'] > 0 ? round(($item['conversion_captured'] / $item['total_revived']) * 100, 2) . '%' : null;
            $data['conversionRate'][] = $c;

            $ac['quote_batch_id'] = $batch;
            $ac['authorized'] = $item['authorized'];
            $ac['captured'] = $item['captured'];
            $ac['ratio'] = $item['authorized'] > 0 ? round(($item['captured'] / $item['authorized']) * 100, 2) . '%' : null;
            $data['leadConversionReport'][] = $ac;

            $rs['quote_batch_id'] = $batch;
            $rs['email_sent_count'] = $item['email_sent_count'];
            $rs['reply_received_count'] = $item['reply_received_count'];

            $rs['ratio'] = $item['email_sent_count'] > 0 ? round(($item['reply_received_count'] / $item['email_sent_count']) * 100, 2) . '%' : null;
            $data['emailConversionReport'][] = $rs;
        }

        return $data;
    }
}
