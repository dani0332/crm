<?php

namespace App\Services\Life;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteBatches;
use App\Traits\AddPremiumAllLobs;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Services\BaseService;
use App\Services\CapiRequestService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteLobs;
use App\Enums\quoteStatusCode;
use App\Services\Life\QuoteStatusService;
use App\Enums\CustomerTypeEnum;
use App\Services\Life\InsuranceProviderService;
use App\Services\CentralService;
use App\Services\Life\UserService;
use App\Enums\LookupsEnum;
use App\Services\Life\CustomerMemberService;
use App\Services\Life\NationalityService;
use App\Services\Life\EmbeddedProductService;
use App\Services\CRUDService;
use App\Services\Life\SendUpdateLogService;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Services\QuoteDocumentService;
use App\Enums\AMLStatusCode;
use App\Enums\TravelQuoteEnum;
use App\Enums\PaymentTooltip;
use App\Services\SplitPaymentService;
use App\Services\Life\CustomerService;

class LifeQuoteService extends BaseService
{
    protected $query;

    use AddPremiumAllLobs;
    use RolePermissionConditions;
    use GenericQueriesAllLobs;
    use PersonalQuoteLobs;

    public const TYPE = quoteTypeCode::Life;
    public const TYPE_ID = QuoteTypeId::Life;

    public function getData($forExport = false, $forTotalLeadsCount = false)
    {
        $quotes = $this->getQuotes($forExport, $forTotalLeadsCount);
        $quoteStatuses = $this->getPersonalQuoteStatuses(self::TYPE_ID)->get();
        $advisors = $this->getPersonalQuoteAdvisors(self::TYPE);
        $authorizedDays = $this->getPaymentAuthorisedDays();
        $renewalBatches = $this->getRenewalBaches();

        return compact('quotes', 'quoteStatuses', 'advisors', 'renewalBatches', 'authorizedDays');
    }

    public function getQuotes($forExport = false, $forTotalLeadsCount = false)
    {
        $query = $this->getQuery($forExport, $forTotalLeadsCount);

        return ($forExport) ? $query->get() : $query->simplePaginate(15)->withQueryString();
    }

    public function getQuery($forExport = false, $forTotalLeadsCount = false)
    {
        $query = PersonalQuote::byQuoteTypeCode(QuoteTypes::LIFE)->with([
            'advisor',
            'quoteStatus',
            'nationality',
            'quoteDetail.lostReason:id,text',
            'renewalBatchModel',
            'paymentStatus',
            'payments',
        ])
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
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount);

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        return $query;
    }

    public function getCardsViewData()
    {
        $quotes = [];

        $quoteStatuses = $this->getPersonalQuoteStatuses(self::TYPE_ID)
            ->whereIn('text', [quoteStatusCode::NEWLEAD, quoteStatusCode::QUOTED, quoteStatusCode::FOLLOWEDUP, quoteStatusCode::NEGOTIATION])
            ->get()->toArray();

        foreach ($quoteStatuses as &$quoteStatus) {
            $quotes[] = $this->getQuotesAgainstQuoteStatus($quoteStatus);
        }
        return [
            'quotes' => $quotes,
            'quoteType' => self::TYPE,
        ];
    }

    private function getQuotesAgainstQuoteStatus($quoteStatus)
    {
        $query = $this->getQuery();
        $query->where('quote_status_id', $quoteStatus['id']);
        $quoteStatus['data']['total_leads'] = $query->count();
        $quoteStatus['data']['total_premium'] = $query->sum('price_with_vat');
        $quoteStatus['data']['leads_list'] = $query->paginate(10);

        return $quoteStatus;
    }

    public function getCardsViewLoadMore($data)
    {
        $quoteStatus = [
            'id' => $data['status']
        ];
        return $this->getQuotesAgainstQuoteStatus($quoteStatus)['data'];
    }

    public function saveLifeQuote($data)
    {
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
            'childrenId' => $data['children_id'],
            'premium' => $data['premium'],
            'tenureOfInsuranceId' => $data['tenure_of_insurance_id'],
            'numberOfYearsId' => $data['number_of_years_id'],
            'isSmoker' => $data['is_smoker'] == 1 ? 1 : 0,
            'gender' => $data['gender'],
            'othersInfo' => $data['others_info'],
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
            'quoteTypeId' => self::TYPE_ID,
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'createdById' => auth()->user()->id,
        ];

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $quoteData);

        return $response;
    }

    public function getPlainQuoteBy($column, $value)
    {
        return PersonalQuote::where($column, $value)->first();
    }

    public function getQuoteBy($column, $value)
    {
        $quote = PersonalQuote::byQuoteTypeCode(QuoteTypes::LIFE)
            ->where($column, $value)
            ->with([
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
                    ]);
                },
                'quoteDetail.lostReason:id,text',
                'quoteDetail.previousAdvisor',
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
                \DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = '.QuoteTypeId::Life.' AND quote_request_id = personal_quotes.id),
                    "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
                as customer_type'),
            ])
            ->firstOrFail();

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        return $quote;
    }

    public function getShowData($uuid)
    {
        $quote = $this->getQuoteBy('uuid', $uuid);

        $payments = $quote->payments;
        $insuranceProviders = app(InsuranceProviderService::class)->byQuoteTypeMapping(self::TYPE_ID);
        $duplicateAllowedLobs = app(CentralService::class)->duplicateAllowedLobsList(self::TYPE, $quote->code);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(self::TYPE, $quote);

        $advisors = app(UserService::class)->getPersonalQuoteAdvisors(self::TYPE);
        $memberRelations = app(LookupService::class)->getBy('key', LookupsEnum::MEMBER_RELATION);
        $membersDetails = app(CustomerMemberService::class)->getBy($quote->id, self::TYPE);
        $quoteStatuses = app(QuoteStatusService::class)->byQuoteTypeId(self::TYPE_ID)->get();
        $lostReasons = app(LostReasonService::class)->getAllOrderedBy('text', 'asc');
        $nationalities = app(NationalityService::class)->getActive();
        $embeddedProducts = app(EmbeddedProductService::class)->byQuoteTypeId(self::TYPE_ID, $quote->id);
        $industryType = app(LookupService::class)->getBy('key', LookupsEnum::COMPANY_TYPE);
        $activities = app(ActivityService::class)->getQuoteActivities(self::TYPE_ID, $quote->id);

        $uboDetails = app(CustomerMemberService::class)->getBy($quote->id, self::TYPE, CustomerTypeEnum::Entity);
        $uboRelations = app(LookupService::class)->getBy('key', LookupsEnum::UBO_RELATION);
        $emirates = app(EmirateService::class)->getActive();

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
            $sendUpdateOptions = app(LookupService::class)->getSendUpdateOptions(QuoteTypes::LIFE->id());
            $sendUpdateLogs = app(SendUpdateLogService::class)->byQuoteUuid($quote->uuid);
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
            'customerAdditionalContacts' => app(CustomerService::class)->getAdditionalContacts($quote->customer_id, $quote->mobile_no),
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
            'paymentMethods' => app(PaymentMethodService::class)->getAll(),
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

    public function getFormOptions()
    {
        return [
            'nationalities' => app(NationalityService::class)->getActive(),
            'currency' => app(CurrencyTypeService::class)->getActive(),
            'purposeOfInsurance' => app(PurposeOfInsuranceService::class)->getActive(),
            'maritalStatus' => app(MaritalStatusService::class)->getActive(),
            'children' => app(LifeChildrenService::class)->getActive(),
            'typeOfInsurance' => app(LifeInsuranceTenureService::class)->getActive(),
            'numberOfYears' => app(LifeNumberOfYearsService::class)->getActive(),
        ];
    }

    public function getEditData($uuid)
    {
        $quote = $this->getQuoteBy('uuid', $uuid);
        abort_if(! $quote, 404);

        $formOptions = $this->getFormOptions();

        return array_merge($formOptions, [
            'quote' => $quote
        ]);
    }

    public function getSelectedLostReason($id)
    {
        $entity = PersonalQuoteDetail::where('personal_quote_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return PersonalQuoteDetail::firstOrCreate(
            ['personal_quote_id' => $id]
        );
    }

    public function getLeadsForAssignment()
    {
        return PersonalQuote::where('quote_type_id', QuoteTypeId::Life)->orderBy('created_at', 'desc')->get();
    }

    public function updateLifeQuote($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->getPlainQuoteBy('uuid', $uuid);

            //check the columns to be updated in personal quotes.
            $quoteData = Arr::only($data, app(PersonalQuote::class)->allowedColumns());
            $quoteData['updated_by_id'] = auth()->user()->id;
            $quote->update($quoteData);

            // check the columns to be updated in life quote request.
            if ($quote->lifeQuote) {
                $quote->lifeQuote()->update(Arr::only($data, app(LifeQuote::class)->allowedColumns()));
            } else {
                $quote->lifeQuote()->create(Arr::only($data, app(LifeQuote::class)->allowedColumns()));
            }

            return $quote;
        });
    }

    public function getDuplicateEntityByCode($code)
    {
        return PersonalQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function processManualLeadAssignment($request): array
    {
        if ($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadsIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        } else {
            $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }
        $userId = (int) $request->assigned_to_id_new;
        $quoteBatch = QuoteBatches::latest()->first();
        Log::info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getPlainQuoteBy('id', $leadId);

            $this->handleAssignment($lead, $userId, $quoteBatch, QuoteTypes::LIFE, PersonalQuoteDetail::class, 'personal_quote_id');
        }

        return $result;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return PersonalQuote::where('uuid', $uuid)->first();
    }

    public function validateRequest($request)
    {
        $userId = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId == null || $request->selectTmLeadId == '' ? $request->entityId : $request->selectTmLeadId;
        if ($leadsIds == '' || $leadsIds == null) {
            return 'Please select lead(s) to assign';
        }
        if (substr($leadsIds, 0, 1) == ',') {
            $leadsIds = substr($leadsIds, 1);
        }
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $leadId) {
            $entity = $this->getPlainQuoteBy('id', $leadId);
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }

        return 'true';
    }
}
