<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\CycleQuote;
use App\Models\PersonalQuote;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CycleQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote.
     *
     * @param  $quoteTypeCode
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::CYCLE->id()),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'cycleMake' => $data['cycle_make'],
            'cycleModel' => $data['cycle_model'],
            'accessories' => $data['accessories'],
            'hasAccident' => boolval($data['has_accident']),
            'hasGoodCondition' => boolval($data['has_good_condition']),
            'assetValue' => $data['asset_value'],
            'yearOfManufactureId' => strval($data['year_of_manufacture_id']),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        info('cycleQuote:'.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {

        $query = $this->byQuoteTypeCode(QuoteTypes::CYCLE)->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
        ])
            ->when(\auth()->user()->hasRole(RolesEnum::CycleAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->when(isset(request()->advisors) && ! empty(request()->advisors), function ($query) {
                $advisors = request()->advisors;
                $query->whereIn('advisor_id', $advisors)->whereNotNull('advisor_id');
            })
            ->when(! empty(request()->advisor_assigned_date), function ($query) {
                $dateArray = request()->advisor_assigned_date;
                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                $query->whereHas('quoteDetail', function ($subQuery) use ($dateFrom, $dateTo) {
                    $subQuery->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
                });
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount);

        $this->adjustQueryByInsurerInvoiceFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        if ($forTotalLeadsCount) {
            // PD Revert
            return 0;
            // return $query->count();
        }

        return ($forExport) ? $query->get() : $query;
    }

    public function fetchExport()
    {
        return $this->byQuoteTypeCode(QuoteTypes::CYCLE)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor'])
            ->when(\auth()->user()->hasRole(RolesEnum::CycleAdvisor), function ($query) {
                $query->where(function ($query) {
                    $query->where('advisor_id', \auth()->user()->id);
                });
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::CYCLE->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, ['first_name', 'last_name', 'email', 'mobile_no', 'dob',  'asset_value']);
            $quoteData['updated_by_id'] = Auth::user()->id;

            $quote->update($quoteData);

            $quote->cycleQuote()->updateOrCreate(
                ['personal_quote_id' => $quote->id],
                Arr::only($data, (new CycleQuote)->allowedColumns())
            );

            return $quote;
        });
    }

    /**
     * get all dropdown options required for form.
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'nationalities' => NationalityRepository::withActive()->get(),
            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
            'insuranceProviders' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->get(),
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        $quote = $this->byQuoteTypeId(QuoteTypes::CYCLE->id())
            ->where($column, $value)
            ->with([
                'cycleQuote',
                'cycleQuote.yearOfManufacture',
                'advisor',
                'nationality',
                'quoteDetail.lostReason',
                'quoteDetail.previousAdvisor',
                'transactionType',
                'insuranceProvider',
                'payments' => function ($q) {
                    $q->with([
                        'paymentSplits' => function ($query) {
                            $query->orderBy('sr_no', 'asc');
                        },
                        'paymentStatus', 'personalPlan', 'paymentMethod', 'paymentStatusLogs', 'insuranceProvider', 'paymentable',
                        'paymentSplits.paymentStatus',
                        'paymentSplits.paymentMethod',
                        'paymentSplits.verifiedByUser',
                        'paymentSplits.documents',
                        'paymentSplits.processJob',
                    ]);
                },
                'customer',
                'createdBy',
                'updatedBy',
                'customer.additionalContactInfo',
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
                'quoteRequestEntityMapping' => function ($entityMapping) {
                    $entityMapping->with('entity');
                },
            ])
            ->select([
                $this->getTable().'.*',
                'policy_expiry_date',
                'policy_start_date',
                'policy_issuance_date',
                \DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = '.QuoteTypeId::Cycle.' AND quote_request_id = '.$this->getTable().'.id),
                    "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
                as customer_type'),
            ])
            ->firstOrFail();
        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);
        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        return $quote;
    }
}
