<?php

namespace App\Repositories;

use App\Enums\AMLStatusCode;
use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\YachtQuote;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class YachtQuoteRepository extends BaseRepository
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
            'quoteTypeId' => intval(QuoteTypes::YACHT->id()),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'companyName' => $data['company_name'],
            'companyAddress' => $data['company_address'],
            'boatDetails' => $data['boat_details'],
            'engineDetails' => $data['engine_details'],
            'claimExperience' => $data['claim_experience'],
            'use' => $data['use'],
            'operatorExperience' => $data['operator_experience'],
            'assetValue' => $data['asset_value'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'nationalityId' => $data['nationality_id'],
        ];

        info('YachtQuote create data : '.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::YACHT->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, ['first_name', 'last_name', 'email', 'mobile_no', 'company_name', 'company_address', 'asset_value', 'gender', 'dob', 'nationality_id']);
            $quoteData['updated_by_id'] = Auth::user()->id;

            $quote->update($quoteData);

            $quote->yachtQuote()->updateOrCreate(
                ['personal_quote_id' => $quote->id],
                Arr::only($data, (new YachtQuote)->allowedColumns())
            );

            $quote->customer()->update([
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'nationality_id' => $data['nationality_id'] ?? null,
            ]);

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
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        $quoteTypeId = QuoteTypes::YACHT->id();
        $quote = $this->byQuoteTypeId($quoteTypeId)
            ->where($column, $value)
            ->with([
                'yachtQuote',
                'advisor',
                'transactionType',
                'nationality',
                'quoteDetail.lostReason',
                'quoteDetail.previousAdvisor',
                'insuranceProvider',
                'insured' => function ($q) use ($quoteTypeId) {
                    $q->where('customer_insured.quote_type_id', $quoteTypeId);
                },
                'payments' => function ($q) {
                    $q->with(['paymentStatus', 'personalPlan', 'paymentMethod', 'paymentable',
                        'paymentSplits.paymentStatus',
                        'paymentSplits.paymentMethod',
                        'paymentSplits.verifiedByUser',
                        'paymentSplits.documents',
                        'paymentSplits.processJob',
                    ]);
                },
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
                    WHERE quote_type_id = '.QuoteTypeId::Yacht.' AND quote_request_id = '.$this->getTable().'.id),
                    "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
                as customer_type'),
            ])
            ->firstOrFail();

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;
        if (isset($data['insured'][0])) {
            $quote->emirates_id_number = $data['insured'][0]['id_type'] == 'emiratesId' ? $data['insured'][0]['id_number'] : null;
        }

        return $quote;
    }

    /**
     * @return mixed
     */
    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {

        $query = $this->byQuoteTypeCode(QuoteTypes::YACHT)->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'quoteDetail',
        ])
            ->when(\auth()->user()->hasRole(RolesEnum::YachtAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
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
            ->withFakeLeadCriteria($forTotalLeadsCount)
            ->select([
                '*',
                DB::raw('
                    CASE
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningPending.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending).'"
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningCleared.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared).'"
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningFailed.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed).'"
                        WHEN insurer_aml_status IS NULL THEN "'.AMLStatusCode::InsurerAMLScreeningNA.'"
                        ELSE insurer_aml_status
                    END AS insurer_aml_status_display
                '),
            ]);

        $this->adjustQueryByInsurerInvoiceFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        if ($forTotalLeadsCount) {
            // PD Revert
            // return $query->count();
            return 0;
        }

        return ($forExport) ? $query->get() : $query;
    }

    public function fetchExport()
    {
        return $this->byQuoteTypeCode(QuoteTypes::YACHT)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
    }

    /**
     * get data by  yacht type.
     *
     * @return mixed
     */
    public function scopeByQuoteTypeCode($query, $quoteTypeCode)
    {
        return $query->whereHas('quoteType', function ($q) use ($quoteTypeCode) {
            $q->where('code', ($quoteTypeCode));
        });
    }
}
