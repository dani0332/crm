<?php

namespace App\Services\Life;

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
use App\Models\ApplicationStorage;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteBatches;
use App\Services\BaseService;
use App\Services\CapiRequestService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\QuoteDocumentService;
use App\Services\SplitPaymentService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteLobs;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Arr;

class LifeQuoteService extends BaseService
{
    protected $query;

    use AddPremiumAllLobs;
    use GenericQueriesAllLobs;
    use PersonalQuoteLobs;
    use RolePermissionConditions;

    public function getLifeQuoteData($isExportRequest = false, $isTotalLeadCountRequest = false)
    {
        $quotes = $this->getLifeQuotes($isExportRequest, $isTotalLeadCountRequest);
        $quoteStatuses = $this->getPersonalQuoteStatuses(QuoteTypeId::Life)->get();
        $advisors = $this->getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $authorizedDays = $this->getPaymentAuthorisedDays();
        $renewalBatches = $this->getRenewalBaches();

        return compact('quotes', 'quoteStatuses', 'advisors', 'renewalBatches', 'authorizedDays');
    }

    public function getLifeQuotes($isExportRequest = false, $isTotalLeadCountRequest = false)
    {
        $query = $this->getLifeQuoteQuery($isExportRequest, $isTotalLeadCountRequest);

        return ($isExportRequest) ? $query->get() : $query->simplePaginate(15)->withQueryString();
    }

    public function getLifeQuoteQuery($isExportRequest = false, $isTotalLeadCountRequest = false)
    {
        $query = PersonalQuote::byQuoteTypeCode(QuoteTypes::LIFE->value)->with([
            'advisor',
            'quoteStatus',
            'nationality',
            'quoteDetail.lostReason:id,text',
            'renewalBatchModel',
            'paymentStatus',
            'payments',
        ])
            ->when(auth()->user()->hasRole(RolesEnum::LifeAdvisor), function ($query) {
                $query->where('advisor_id', auth()->user()->id);
            })
            ->when(! empty(request()->advisor_assigned_date), function ($query) {
                $advisorAssignedDateRange = request()->advisor_assigned_date;
                $advisorAssignedDateFrom = Carbon::parse($advisorAssignedDateRange[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $advisorAssignedDateTo = Carbon::parse($advisorAssignedDateRange[1])->endOfDay()->toDateTimeString();
                $query->whereHas('quoteDetail', function ($subQuery) use ($advisorAssignedDateFrom, $advisorAssignedDateTo) {
                    $subQuery->whereBetween('advisor_assigned_date', [$advisorAssignedDateFrom, $advisorAssignedDateTo]);
                });
            })
            ->filter(! $isExportRequest , $isTotalLeadCountRequest)
            ->withFakeLeadCriteria($isTotalLeadCountRequest);

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        return $query;
    }

    public function getCardsViewData()
    {
        $lifeQuotes = [];

        $quoteStatuses = $this->getPersonalQuoteStatuses(QuoteTypeId::Life)
            ->whereIn('text', [quoteStatusCode::NEWLEAD, quoteStatusCode::QUOTED, quoteStatusCode::FOLLOWEDUP, quoteStatusCode::NEGOTIATION])
            ->get()->toArray();

        foreach ($quoteStatuses as &$quoteStatus) {
            $lifeQuotes[] = $this->getLifeQuotesAgainstQuoteStatus($quoteStatus);
        }

        return [
            'quotes' => $lifeQuotes,
            'quoteType' => QuoteTypes::LIFE->value,
        ];
    }

    private function getLifeQuotesAgainstQuoteStatus($quoteStatus)
    {
        $query = $this->getLifeQuoteQuery();
        $query->where('quote_status_id', $quoteStatus['id']);
        $quoteStatus['data']['total_leads'] = $query->count();
        $quoteStatus['data']['total_premium'] = $query->sum('price_with_vat');
        $quoteStatus['data']['leads_list'] = $query->paginate(10);

        return $quoteStatus;
    }

    public function getCardsViewLoadMore($data)
    {
        $quoteStatus = [
            'id' => $data['status'],
        ];

        return $this->getLifeQuotesAgainstQuoteStatus($quoteStatus)['data'];
    }

    public function saveLifeQuote($data)
    {
        $lifeQuote = [
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
            'quoteTypeId' => QuoteTypeId::Life,
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'createdById' => auth()->user()->id,
        ];

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $lifeQuote);

        return $response;
    }

    public function getPlainQuoteBy($column, $value)
    {
        return PersonalQuote::where($column, $value)->first();
    }

    public function getQuoteBy($column, $value)
    {
        $lifeQuote = PersonalQuote::byQuoteTypeCode(QuoteTypes::LIFE->value)
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
                DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = '.QuoteTypeId::Life.' AND quote_request_id = personal_quotes.id),
                    "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
                as customer_type'),
            ])
            ->firstOrFail();

        $data = ! empty($lifeQuote) ? $lifeQuote->toArray() : [];
        $lifeQuote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $lifeQuote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $lifeQuote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        return $lifeQuote;
    }

    public function getShowData($uuid)
    {
        $lifeQuote = $this->getQuoteBy('uuid', $uuid);

        $payments = $lifeQuote->payments;
        $insuranceProviders = app(InsuranceProviderService::class)->byQuoteTypeMapping(QuoteTypeId::Life);
        $duplicateAllowedLobs = app(CentralService::class)->duplicateAllowedLobsList(QuoteTypes::LIFE->value, $lifeQuote->code);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails($lifeQuote);

        $advisors = $this->getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $memberRelations = app(LookupService::class)->getBy('key', LookupsEnum::MEMBER_RELATION);
        $membersDetails = app(CustomerMemberService::class)->getBy($lifeQuote->id, QuoteTypes::LIFE->value);
        $quoteStatuses = $this->getPersonalQuoteStatuses(QuoteTypeId::Life)->get();
        $lostReasons = app(LostReasonService::class)->getAllOrderedBy('text', 'asc');
        $nationalities = app(NationalityService::class)->getActive();
        $embeddedProducts = app(EmbeddedProductService::class)->byQuoteTypeId(QuoteTypeId::Life, $lifeQuote->id);
        $industryType = app(LookupService::class)->getBy('key', LookupsEnum::COMPANY_TYPE);
        $activities = app(ActivityService::class)->getQuoteActivities(QuoteTypeId::Life, $lifeQuote->id);

        $uboDetails = app(CustomerMemberService::class)->getBy($lifeQuote->id, QuoteTypes::LIFE->value, CustomerTypeEnum::Entity);
        $uboRelations = app(LookupService::class)->getBy('key', LookupsEnum::UBO_RELATION);
        $emirates = app(EmirateService::class)->getActive();

        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($lifeQuote, QuoteTypeId::Life, $quoteStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }

        [$hasPolicyIssuedStatus, $sendUpdateOptions, $sendUpdateLogs, $sendUpdateEnum] = $this->prepareSendUpdateData($lifeQuote);

        $activitiesData = $this->prepareActivitiesData($activities);

        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Life);

        $isQuoteDocumentEnabled = app(BaseService::class)->quoteDocumentEnabled(QuoteTypes::LIFE->value);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::LIFE->value, $lifeQuote->id);
        $bookPolicyDetails = $this->bookPolicyPayload($lifeQuote, QuoteTypes::LIFE->value, $payments, $quoteDocuments);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($lifeQuote);
        $amlStatusName = AMLStatusCode::getName($lifeQuote->aml_status);
        $customerAdditionalContacts = app(CustomerService::class)->getAdditionalContacts($lifeQuote->customer_id, $lifeQuote->mobile_no);
        $isNewPaymentStructure = app(SplitPaymentService::class)->isNewPaymentStructure($lifeQuote->payments);

        return [
            'documentTypes' => $documentTypes,
            'storageUrl' => storageUrl(),
            'quoteType' => QuoteTypes::LIFE->value,
            'quoteTypeId' => QuoteTypeId::Life,
            'quoteStatuses' => $quoteStatuses,
            'quote' => $lifeQuote,
            'amlStatusName' => $amlStatusName,
            'record' => $lifeQuote,
            'activities' => $activitiesData,
            'advisors' => $advisors,
            'allowedDuplicateLOB' => $duplicateAllowedLobs,
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'lostReasons' => $lostReasons,
            'modelType' => QuoteTypes::LIFE->value,
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
            'bookPolicyDetails' => $bookPolicyDetails,
            'vatPercentage' => $vatPercentage,
            'isNewPaymentStructure' => $isNewPaymentStructure,
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
            'quote' => $quote,
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
                $lifeQuoteData = Arr::only($data, app(LifeQuote::class)->allowedColumns());
                $quote->lifeQuote->fill($lifeQuoteData);
                $quote->lifeQuote->save();
            } else {
                $lifeQuoteData = Arr::only($data, app(LifeQuote::class)->allowedColumns());
                $lifeQuote = $quote->lifeQuote()->create($lifeQuoteData);
                $lifeQuote->audit();
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

        info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);

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

    public function correctHistoricData($quote)
    {
        /* Start - Temporarily adding for correcting historic data  */
        app(PaymentService::class)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data  */
    }

    private function prepareActivitiesData($activities)
    {
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

        return $activitiesData;
    }

    private function prepareSendUpdateData($quote)
    {
        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];

        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = app(LookupService::class)->getSendUpdateOptions(QuoteTypeId::Life);
            $sendUpdateLogs = app(SendUpdateLogService::class)->byQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        return [
            $hasPolicyIssuedStatus,
            $sendUpdateOptions,
            $sendUpdateLogs,
            $sendUpdateEnum,
        ];
    }
}
