<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\HomeAccomodationType;
use App\Models\HomePossessionType;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    public function fetchCreate($request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = [
            'firstName' => $request['first_name'],
            'lastName' => $request['last_name'],
            'email' => $request['email'],
            'mobileNo' => $request['mobile_no'],
            'gender' => $request['gender'],
            'microchipNo' => $request['microchip_no'],
            'petTypeId' => $request['pet_type_id'],
            'petAgeId' => $request['pet_age_id'],
            'breedOfPet1' => $request['breed_of_pet1'],
            'isMicrochipped' => $request['is_microchipped'],
            'isNeutered' => $request['is_neutered'],
            'isMixedBreed' => $request['is_mixed_breed'],
            'hasInjury' => $request['has_injury'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'quoteTypeId' => intval(QuoteTypes::PET->id()),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        $response = Capi::request('/api/v1-save-personal-quote', 'post', $dataArr);

        if (isset($response->quoteUID)) {
            $quote = $this->byQuoteTypeId(QuoteTypes::PET->id())->where('uuid', $response->quoteUID)->firstOrFail();
        }

        return $response;
    }

    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::PET->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no',
            ]);

            $quoteData['updated_by_id'] = Auth::user()->id;
            $quote->update($quoteData);

            $quote->petQuote()->updateOrCreate(
                ['personal_quote_id' => $quote->id],
                Arr::only($data, (new PetQuote)->allowedColumns())
            );

            return $quote;
        });
    }

    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {

        $query = $this->byQuoteTypeCode(QuoteTypes::PET)->with([
            'quoteStatus',
            'quoteDetail',
            'petQuote.accomodationType:id,text',
            'petQuote.possessionType:id,text',
            'petQuote.petAge:id,text',
            'petQuote.petType:id,text',
            'currentlyInsuredWith',
            'advisor',
            'petQuote.petQuoteRequestDetail.lostReason:id,text',
            'paymentStatus',
            'payments',
            'renewalBatchModel',
            'quoteDetail',
        ])
            ->when(\auth()->user()->hasRole(RolesEnum::PetAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->when(! empty(request()->is_renewal), function ($query) {
                $isRenewal = request()->is_renewal;
                if ($isRenewal == quoteTypeCode::yesText) {
                    $query->whereNotNull('previous_quote_policy_number');
                } elseif ($isRenewal == quoteTypeCode::noText) {
                    $query->whereNull('previous_quote_policy_number');
                }
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

    public function fetchGetBy($column, $value)
    {
        $quote = $this->byQuoteTypeId(QuoteTypes::PET->id())
            ->where($column, $value)
            ->with([
                'petQuote.accomodationType:id,text',
                'petQuote.possessionType:id,text',
                'petQuote.petAge:id,text',
                'petQuote.petType:id,text',
                'plans:id,text',
                'advisor',
                'nationality',
                'quoteDetail.lostReason',
                'quoteDetail.previousAdvisor',
                'transactionType',
                'payments' => function ($q) {
                    $q->with([
                        'paymentStatus',
                        'personalPlan',
                        'paymentMethod',
                        'paymentStatusLogs',
                        'insuranceProvider',
                        'paymentable',
                        'paymentSplits' => function ($q) {
                            $q->with([
                                'paymentStatus',
                                'paymentMethod',
                                'documents',
                                'verifiedByUser',
                                'processJob',
                            ])->orderBy('sr_no', 'asc');
                        },
                    ]);
                },
                'createdBy',
                'updatedBy',
                'customer.additionalContactInfo',
                'insuranceProvider',
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
                'quoteRequestEntityMapping' => function ($entityMapping) {
                    $entityMapping->with('entity');
                },
                'quoteDetail',
            ])
            ->select([
                $this->getTable().'.*',
                'policy_expiry_date',
                'policy_start_date',
                'policy_issuance_date',
                \DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = '.QuoteTypeId::Pet.' AND quote_request_id = '.$this->getTable().'.id),
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

    /**
     * get all dropdown options required for form.
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'pet_ages' => LookupRepository::where('key', LookupsEnum::PET_AGES)->get(),
            'pet_types' => LookupRepository::where('key', LookupsEnum::PET_TYPES)->get(),
            'accomodation_types' => HomeAccomodationType::all(),
            'possession_types' => HomePossessionType::all(),
        ];
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        $dataArr['quoteTypeId'] = intval(QuoteTypes::PET->id());

        return Capi::request('/api/v1-save-personal-quote', 'post', $dataArr);
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider']
        )->orderBy('created_at', 'desc');
    }
}
