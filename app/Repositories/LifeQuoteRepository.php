<?php

namespace App\Repositories;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Facades\Capi;
use App\Models\ApplicationStorage;
use App\Models\Emirate;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Services\BaseService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LifeQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    public function fetchCreate($data)
    {
        // Log sub-source parameters
        LoggerService::info('LifeQuoteRepository fetchCreate called with sub-source parameters', [
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $quoteData = [
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'dob' => $data['dob'],
            'sumInsuredValue' => $data['sum_insured_value'],
            'nationalityId' => $data['nationality_id'],
            'sumInsuredCurrencyId' => $data['sum_insured_currency_id'],
            'maritalStatusId' => $data['marital_status_id'],
            'purposeOfInsuranceId' => $data['purpose_of_insurance_id'],
            'tenureOfInsuranceId' => $data['tenure_of_insurance_id'],
            'numberOfYearsId' => $data['number_of_years_id'],
            'isSmoker' => $data['is_smoker'] == 1 ? 1 : 0,
            'gender' => $data['gender'],
            'othersInfo' => $data['others_info'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'bmi' => $data['bmi'],
            'age' => $data['age'],
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
            'quoteTypeId' => intval(QuoteTypes::LIFE->id()),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'createdById' => auth()->user()->id,
            // Sub-source fields
            'subSourceId' => $data['sub_source_id'] ?? null,
            'subSourceOptionsId' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        $response = Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);

        if (isset($response->quoteUID)) {
            $quote = $this->where('uuid', $response->quoteUID)->firstOrFail();
            $quote->lifeQuote()->update([
                'height' => $quoteData['height'],
                'weight' => $quoteData['weight'],
                'bmi' => $quoteData['bmi'],
                'age' => $quoteData['age'],
            ]);
        }

        return $response;
    }

    public function fetchUpdate($uuid, $data)
    {
        // Log sub-source parameters for update
        LoggerService::info('LifeQuoteRepository fetchUpdate called with sub-source parameters', [
            'uuid' => $uuid,
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::LIFE->id())->where('uuid', $uuid)->firstOrFail();

            // check the columns to be updated in personal quotes.
            $quoteData = Arr::only($data, $this->allowedColumns());
            $quoteData['updated_by_id'] = auth()->user()->id;

            // Add sub-source fields explicitly
            if (isset($data['sub_source_id'])) {
                $quoteData['sub_source_id'] = $data['sub_source_id'];
            }
            if (isset($data['sub_source_options_id'])) {
                $quoteData['sub_source_options_id'] = $data['sub_source_options_id'];
            }
            // Removed primary_ref_id mapping
            if (isset($data['notes'])) {
                $quoteData['notes'] = $data['notes'];
            }

            $quote->update($quoteData);

            // check the columns to be updated in life quote request.
            if ($quote->lifeQuote) {
                $quote->lifeQuote()->update(Arr::only($data, (new LifeQuote)->allowedColumns()));
            } else {
                $quote->lifeQuote()->create(Arr::only($data, (new LifeQuote)->allowedColumns()));
            }

            return $quote;
        });
    }

    public function fetchGetData($forExport = false)
    {
        $query = $this->byQuoteTypeCode(QuoteTypes::LIFE)->with([
            'advisor',
            'quoteStatus',
            'nationality',
            'quoteDetail.lostReason:id,text',
            'renewalBatchModel',
            'paymentStatus',
            'payments',
            'customer',
            'latestInsured' => function ($q) {
                $q->where('customer_insured.quote_type_id', QuoteTypeId::Life);
            }])
            ->when(\auth()->user()->hasRole(RolesEnum::LifeAdvisor), function ($query) {
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
            ->filter()
            ->filterBySegment('life_quote_request')
            ->filterByPrivateClient(request('private_client'))
            ->withFakeLeadCriteria()
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
            ])
            ->orderBy('life_quote_request.created_at', 'desc');

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        return ($forExport) ? $query->get() : $query->simplePaginate(15)->withQueryString();
    }

    public function fetchExport()
    {
        return $this->with(['advisor', 'quoteStatus', 'nationality'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
    }

    public function fetchGetBy($column, $value)
    {
        $quote = $this->where($column, $value)->with(['advisor', 'quoteStatus', 'nationality', 'lifeQuote.previousAdvisor', 'lifeQuote.lifeQuoteRequestDetail.lostReason',
            'lifeQuote.purposeOfInsurance', 'lifeQuote.children', 'lifeQuote.currency', 'lifeQuote.insuranceTenure', 'lifeQuote.numberOfYears', 'lifeQuote.maritalStatus',
            'lifeQuote.paymentStatus', 'customer.additionalContactInfo', 'transactionType', 'insuranceProvider',
            'payments.paymentMethod', 'payments.paymentStatus', 'payments.paymentSplits.paymentStatus', 'payments.paymentSplits.paymentMethod',
            'payments.paymentSplits.documents', 'payments.paymentSplits.verifiedByUser', 'payments.paymentSplits.processJob',
            'latestInsured' => function ($q) {
                $q->where('customer_insured.quote_type_id', QuoteTypeId::Life);
            },
            'latestInsured.insuredKyc:id,insured_id',
            'quoteRequestEntityMapping' => function ($entityMapping) {
                $entityMapping->with('entity');
            },
        ])->with([
            'advisor',
            'quoteStatus',
            'nationality',
            'lifeQuote' => function ($q) {
                $q->with([
                    'children',
                    'currency',
                    'maritalStatus',
                    'purposeOfInsurance',
                    'insuranceTenure',
                    'numberOfYears',
                    'previousAdvisor',
                ]);
            },
            'quoteDetail.lostReason:id,text',
            'paymentStatus',
            'customer.additionalContactInfo',
            'transactionType',
            'insuranceProvider',
            'payments' => function ($q) {
                $q->with([
                    'paymentMethod',
                    'paymentStatus',
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
            'documents' => function ($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            },
            'quoteRequestEntityMapping' => function ($entityMapping) {
                $entityMapping->with('entity');
            },
        ])
            ->select([
                'personal_quotes.*',
                'policy_expiry_date',
                'policy_start_date',
                'policy_issuance_date',
            ])->firstOrFail();

        $quote->customer_type = $quote->latestInsured?->customer_type ?? CustomerTypeEnum::Individual;
        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;
        if (isset($data['latestInsured'])) {
            $quote->emirates_id_number = $data['latestInsured']['id_type'] == 'emiratesId' ? $data['latestInsured']['id_number'] : null;
        }

        return $quote;
    }

    public function fetchGetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

    public function fetchExportData($requestParams = [])
    {
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        $query = $this->with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason'])
            ->filter(paginate: false)
            ->withFakeLeadCriteria();
        $this->adjustQueryByDateFilters($query, 'life_quote_request');

        return $query->orderBy('life_quote_request.created_at', 'desc');
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::LIFE->value).'-quote', 'post', $dataArr);
    }

    public function fetchGetShowFormOptions($quote)
    {
        $payments = $quote->payments;
        $duplicateAllowedLobs = (new CentralService)->duplicateAllowedLobsList(QuoteTypes::LIFE->value, $quote->code);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::LIFE->value, $quote);

        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $membersDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::LIFE->name);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $nationalities = NationalityRepository::withActive()->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::LIFE->id(), $quote->id);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::LIFE->id());
        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::LIFE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::LIFE->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::LIFE->id(), $quoteStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::LIFE->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $activitiesData = [];
        foreach ($activities as $activity) {
            $activitiesData[] = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => $activity->assignee->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
                'quote_status_id' => $activity->quote_status_id,
                'quote_status' => $activity?->quoteStatus,
                'user_id' => $activity?->user_id,
            ];
        }

        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Life);

        $isQuoteDocumentEnabled = app(BaseService::class)->quoteDocumentEnabled(QuoteTypes::LIFE->value);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::LIFE->value, $quote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($quote, QuoteTypes::LIFE->value, $payments, $quoteDocuments);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return [
            'documentTypes' => $documentTypes,
            'storageUrl' => storageUrl(),
            'quoteType' => QuoteTypes::LIFE,
            'quoteTypeId' => QuoteTypeId::Life,
            'quoteStatuses' => $quoteStatuses,
            'quote' => $quote,
            'amlStatusName' => $amlStatusName,
            'record' => $quote,
            'activities' => $activitiesData,
            'advisors' => $advisors,
            'allowedDuplicateLOB' => $duplicateAllowedLobs,
            'customerAdditionalContacts' => CustomerRepository::GetAdditionalContacts($quote->customer_id, $quote->mobile_no),
            'lostReasons' => $lostReasons,
            'modelType' => QuoteTypes::LIFE,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::LifeManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'nationalities' => $nationalities,
            'memberRelations' => $memberRelations,
            'membersDetails' => $membersDetails,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'paymentMethods' => (new LookupService)->getPaymentMethods(),
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'payments' => $payments,
            'insuranceProviders' => $insuranceProviders,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],

            'enums' => [
                'travelQuoteEnum' => TravelQuoteEnum::asArray(),
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'vatPercentage' => $vatPercentage,
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'sendUpdateEnum' => $sendUpdateEnum,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
        ];
    }

    public function fetchGetCardData($request)
    {
        $leadStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())
            ->whereIn('text', [quoteStatusCode::NEWLEAD, quoteStatusCode::QUOTED, quoteStatusCode::FOLLOWEDUP, quoteStatusCode::NEGOTIATION])
            ->get()->toArray();

        $leadStatuses = array_map(function ($item) use ($request) {
            $item['data'] = getDataAgainstStatus(QuoteTypes::LIFE->value, $item['id'], $request);

            return $item;
        }, $leadStatuses);

        return [
            'quotes' => array_values($leadStatuses),
            'quoteType' => QuoteTypes::LIFE->value,
        ];
    }
}
