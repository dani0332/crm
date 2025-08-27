<?php

namespace App\Services\Life;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LifeRiderEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTermEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\CurrencyCoverage;
use App\Models\CurrencyType;
use App\Models\DocumentType;
use App\Models\InsuranceProviderPlan;
use App\Models\LifeInsuranceTenure;
use App\Models\LifeNumberOfYears;
use App\Models\LifeQuote;
use App\Models\LifeRider;
use App\Models\LifeRiderOption;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteBatches;
use App\Repositories\CurrencyTypeRepository;
use App\Repositories\UserRepository;
use App\Services\BaseService;
use App\Services\CapiRequestService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\KenService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SplitPaymentService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteLobs;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Arr;
use PDF;

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
        $leadStatuses = $this->getPersonalQuoteStatuses(QuoteTypeId::Life)->get();
        $advisors = $this->getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $authorizedDays = $this->getPaymentAuthorisedDays();
        $renewalBatches = $this->getRenewalBaches();
        $typesOfInsurance = LifeInsuranceTenure::withActive()->get();
        $numberOfYears = LifeNumberOfYears::withActive()->get();
        $currency = CurrencyType::withActive()->get();
        $planSubTypes = Lookup::where('key', LookupsEnum::LIFE_PLAN_SUB_TYPE)->select('id', 'text')->get();
        $quoteSegments = QuoteSegmentEnum::withLabels(QuoteTypeId::Life);

        return compact('quotes', 'leadStatuses', 'advisors', 'renewalBatches', 'authorizedDays', 'typesOfInsurance', 'numberOfYears', 'currency', 'planSubTypes', 'quoteSegments');
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
            'lifeQuote' => function ($q) {
                $q->with([
                    'insuranceTenure',
                    'numberOfYears',
                ]);
            },
            'insuranceProviderPlan' => function ($q) {
                $q->with([
                    'subType:id,code',
                ]);
            },
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
            ->when(! empty(request()->tenure_of_insurance_id), function ($query) {
                $query->whereHas('insuranceProviderPlan', function ($subQuery) {
                    $subQuery->where('sub_type_id', request()->tenure_of_insurance_id);
                });
            })
            ->when(! empty(request()->number_of_years_id), function ($query) {
                $query->whereHas('lifeQuote', function ($subQuery) {
                    $subQuery->where('number_of_years_id', request()->number_of_years_id);
                });
            })
            ->when(! empty(request()->sum_insured_range) && ! empty(request()->sum_insured_currency_id), function ($query) {
                $query->whereHas('lifeQuote', function ($subQuery) {
                    $subQuery->where('sum_insured_currency_id', request()->sum_insured_currency_id);
                });
                switch (request()->sum_insured_range) {
                    case 'lt500k':
                        $query->whereHas('lifeQuote', function ($subQuery) {
                            $subQuery->where('sum_insured_value', '<', 500000);
                        });
                        break;
                    case '500k-1m':
                        $query->whereHas('lifeQuote', function ($subQuery) {
                            $subQuery->whereBetween('sum_insured_value', [500000, 999999]);
                        });
                        break;
                    case 'gte1m':
                        $query->whereHas('lifeQuote', function ($subQuery) {
                            $subQuery->where('sum_insured_value', '>=', 1000000);
                        });
                    default:
                        break;
                }
            })
            ->when(! empty(request()->authorize_date), function ($query) {
                $authorizeDates = request()->authorize_date;
                if (is_array($authorizeDates) && count($authorizeDates) >= 2) {
                    $startDate = $authorizeDates[0];
                    $endDate = $authorizeDates[1];

                    if ($startDate && $endDate) {
                        $query->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereBetween('authorized_at', [
                                Carbon::parse($startDate)->startOfDay(),
                                Carbon::parse($endDate)->endOfDay(),
                            ]);
                        });
                    }
                }
            })
            ->when(! empty(request()->captured_date), function ($query) {
                $capturedDates = request()->captured_date;
                if (is_array($capturedDates) && count($capturedDates) >= 2) {
                    $startDate = $capturedDates[0];
                    $endDate = $capturedDates[1];

                    if ($startDate && $endDate) {
                        $query->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereBetween('captured_at', [
                                Carbon::parse($startDate)->startOfDay(),
                                Carbon::parse($endDate)->endOfDay(),
                            ]);
                        });
                    }
                }
            })
            ->filter(! $isExportRequest, $isTotalLeadCountRequest)
            ->withFakeLeadCriteria($isTotalLeadCountRequest);

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        return $query;
    }

    public function getCardsViewData()
    {
        $lifeQuotes = [];

        $quoteStatusTexts = [quoteStatusCode::QUOTED, quoteStatusCode::FOLLOWEDUP, quoteStatusCode::APPLICATION_SUBMITTED, quoteStatusCode::NEGOTIATION, quoteStatusCode::POLICY_BOOKED];
        $quoteStatuses = $this->getPersonalQuoteStatuses(QuoteTypeId::Life)
            ->whereIn('text', $quoteStatusTexts)
            ->get()->toArray();

        $statusOrderMap = array_flip($quoteStatusTexts);

        usort($quoteStatuses, function ($a, $b) use ($statusOrderMap) {
            $posA = isset($statusOrderMap[$a['text']]) ? $statusOrderMap[$a['text']] : PHP_INT_MAX;
            $posB = isset($statusOrderMap[$b['text']]) ? $statusOrderMap[$b['text']] : PHP_INT_MAX;

            return $posA <=> $posB;
        });

        foreach ($quoteStatuses as &$quoteStatus) {
            $lifeQuotes[] = $this->getLifeQuotesAgainstQuoteStatus($quoteStatus);
        }

        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();
        $typesOfInsurance = LifeInsuranceTenure::withActive()->get();
        $numberOfYears = LifeNumberOfYears::withActive()->get();
        $currency = CurrencyType::withActive()->get();
        $planSubTypes = Lookup::where('key', LookupsEnum::LIFE_PLAN_SUB_TYPE)->select('id', 'text')->get();

        return [
            'quotes' => $lifeQuotes,
            'quoteType' => QuoteTypes::LIFE->value,
            'quoteTypeId' => QuoteTypeId::Life,
            'leadStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'renewalBatches' => $renewalBatches,
            'typesOfInsurance' => $typesOfInsurance,
            'numberOfYears' => $numberOfYears,
            'currency' => $currency,
            'planSubTypes' => $planSubTypes,
        ];
    }

    private function getLifeQuotesAgainstQuoteStatus($quoteStatus)
    {
        $query = $this->getLifeQuoteQuery();
        $query->where('quote_status_id', $quoteStatus['id']);
        $baseQuery = clone $query;
        $quoteStatus['data']['total_leads'] = $query->count();
        $quoteStatus['data']['total_premium'] = $query->sum('price_with_vat');
        $quoteStatus['data']['leads_list'] = $query->paginate(10);

        $quoteIds = $baseQuery->select('id')->get()->pluck('id');
        $quoteStatus['data']['total_sum_insured_value'] = LifeQuote::whereIn('personal_quote_id', $quoteIds)->sum('sum_insured_value');

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
            'quoteTypeId' => QuoteTypeId::Life,
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'createdById' => auth()->user()->id,
        ];

        return CapiRequestService::sendCAPIRequest('/api/v2-save-life-quote', $lifeQuote);
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
                'quoteCustomerPlan',
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
                'insuranceProviderPlan.insuranceProvider',
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
                'latestInsured' => function ($q) {
                    $q->where('customer_insured.quote_type_id', QuoteTypeId::Life);
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

        $quoteNotes = app(LifeQuoteNoteService::class)->getNotes($lifeQuote);
        $noteDocumentType = DocumentType::where('code', DocumentTypeCode::OD)->first();

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
        $ecomLifeInsuranceQuoteUrl = config('constants.ECOM_LIFE_INSURANCE_QUOTE_URL');
        $currencies = app(CurrencyTypeService::class)->getActive();
        $lifeRiders = LifeRider::where('type', 'checkbox')->whereIn('code', [LifeRiderEnum::CRITICAL_ILLNESS, LifeRiderEnum::PERMANENT_AND_TOTAL_DISABILITY, LifeRiderEnum::WAIVER_OF_PREMIUM])->get();
        $emailStatuses = app(BaseService::class)->getEmailStatus(QuoteTypeId::Life, $lifeQuote->id);
        $lifeCutOffDate = ApplicationStorage::where('key_name', ApplicationStorageEnums::LIFE_CUT_OFF_DATE)->first()->value ?? null;

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
            'quoteNotes' => $quoteNotes,
            'noteDocumentType' => $noteDocumentType,
            'ecomLifeInsuranceQuoteUrl' => $ecomLifeInsuranceQuoteUrl,
            'currencies' => $currencies,
            'lifeRiders' => $lifeRiders,
            'paymentTerms' => PaymentTermEnum::asArray(),
            'emailStatuses' => $emailStatuses,
            'currencyOptions' => CurrencyTypeRepository::withActive()->get(),
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'lifeCutOffDate' => $lifeCutOffDate,
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
            'paymentTerms' => PaymentTermEnum::asArray(),
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
        $isAnyFieldChanged = false;
        $quote = null;

        LoggerService::startQuoteLogging($uuid);

        [$quote, $isAnyFieldChanged] = DB::transaction(function () use ($uuid, $data) {
            $isAnyFieldChanged = false;

            $quote = $this->getPlainQuoteBy('uuid', $uuid);

            $quoteData = Arr::only($data, app(PersonalQuote::class)->allowedColumns());
            $quoteData['updated_by_id'] = auth()->user()->id;
            $quote->update($quoteData);

            if ($quote->lifeQuote) {
                LoggerService::info('fn: updateLifeQuote - Life Quote Found');

                $fieldsToRevisePlans = [
                    'dob',
                    'sum_insured_value',
                    'nationality_id',
                    'sum_insured_currency_id',
                    'marital_status_id',
                    'purpose_of_insurance_id',
                    'number_of_years_id',
                    'height',
                    'weight',
                    'age',
                    'bmi',
                    'is_smoker',
                    'gender',
                ];

                $lifeQuote = $quote->lifeQuote;
                foreach ($fieldsToRevisePlans as $field) {
                    if (isset($data[$field]) && $data[$field] != $lifeQuote->$field) {
                        $isAnyFieldChanged = true;
                        break;
                    }
                }

                $lifeQuoteData = Arr::only($data, app(LifeQuote::class)->allowedColumns());
                $quote->lifeQuote->fill($lifeQuoteData);
                $quote->lifeQuote->save();
            } else {
                LoggerService::info('fn: updateLifeQuote - Life Quote Not Found, Creating New One');

                $lifeQuoteData = Arr::only($data, app(LifeQuote::class)->allowedColumns());
                $lifeQuote = $quote->lifeQuote()->create($lifeQuoteData);
                $lifeQuote->audit();
            }

            return [$quote, $isAnyFieldChanged];
        });

        if ($isAnyFieldChanged) {
            $this->getQuotePlans($uuid, true);
            LoggerService::info('fn: updateLifeQuote - Fields Changed, Plans to be revised');
        }

        return $quote;
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

        $error = null;

        if ($leadsIds == '' || $leadsIds == null) {
            $error = 'Please select lead(s) to assign';
        } elseif ($userId == '' || $userId == null) {
            $error = 'Please select user to assign leads';
        } else {
            if (substr($leadsIds, 0, 1) == ',') {
                $leadsIds = substr($leadsIds, 1);
            }

            $leadsIds = array_map('intval', explode(',', $leadsIds));
            foreach ($leadsIds as $leadId) {
                $entity = $this->getPlainQuoteBy('id', $leadId);
                if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                    $error = 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
                    break;
                }
            }
        }

        return $error ?? 'true';

    }

    public function correctHistoricData($quote)
    {
        /* Start - Temporarily adding for correcting historic data */
        app(PaymentService::class)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data */
    }

    public function getQuotePlans(string $uuid, bool $getLatestRating = false)
    {
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-life-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $uuid,
            'getLatestRating' => $getLatestRating,
            'lang' => 'en',
            'callSource' => 'imcrm',
        ];

        $client = new \GuzzleHttp\Client;

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'body' => json_encode($plansDataArr),
                    'timeout' => $plansApiTimeout,
                ]
            );

            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $kenRequest->getBody();

                return json_decode($getContents);
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'No Plans were found for the selected quote.';
            }

            return $responseBodyAsString;
        }
    }

    public function getProviderPlans($providerId)
    {
        return InsuranceProviderPlan::where(['provider_id' => $providerId, 'quote_type_id' => QuoteTypeId::Life])->active()->get();
    }

    public function lifePlanCreateQuote($uuid, $data)
    {
        $reqData = [
            'quoteUID' => $uuid,
            'update' => $data['update'],
            'isVariant' => $data['isVariant'],
            'isUW' => $data['isUW'],
            'plans' => [$data],
        ];

        LoggerService::info('fn: lifePlanCreateQuote', extra: [
            'data' => $reqData,
            'url' => '/save-manual-life-quote-plan',
        ]);

        return app(abstract: KenService::class)->request('/save-manual-life-quote-plan', 'post', $reqData);
    }

    public function getLifeProviderPlan($data)
    {
        LoggerService::info('fn: getLifeProviderPlan', extra: [
            'data' => $data,
        ]);

        return app(abstract: KenService::class)->request('/fetch-life-provider-plan', 'post', $data);
    }

    /* This function will select the Plan details in the Quote */
    public function selectPlan(string $quoteId, int $planId, int $version = 0, $saveQuote = false, $isUW = false)
    {
        LoggerService::info('fn: selectPlan', extra: [
            'planId' => $planId,
            'version' => $version,
            'saveQuote' => $saveQuote,
            'isUW' => $isUW,
            'quoteTypeId' => QuoteTypes::getIdFromValue('Life'),
        ]);

        // Creating Form Data
        $formData = [
            'quoteUID' => $quoteId,
            'planId' => $planId,
            'version' => $version,
            'isUW' => $isUW,
            'quoteTypeId' => QuoteTypes::getIdFromValue('Life'),
        ];

        if ($saveQuote) {
            $formData['saveQuote'] = true;
        }

        return app(KenService::class)->request('/process-life-quote-plan', 'post', $formData);
    }

    public function exportPlansPdf(string $quoteType, array $data = [])
    {
        $quotePlans = $this->quotePlans($data);

        $quote = PersonalQuote::where('uuid', $data['quote_uuid'])->first();

        if (! $quotePlans || ! isset($quotePlans->quotes) || ! isset($quotePlans->quotes->plans)) {
            LoggerService::info('fn: exportPlansPdf - No plans found for the quote');

            return ['pdf' => null, 'name' => null];
        }

        $lifePlans = $quotePlans->quotes->plans;

        $planIds = collect($lifePlans)->take(5)->pluck('_id')->toArray();

        $pdf = PDF::setOption([
            'isHtml5ParserEnabled' => true,
            'dpi' => 150,
            'isRemoteEnabled' => true,
        ])->loadView('pdf.life.comparision_pdf', compact('quote', 'planIds', 'lifePlans'));

        return ['pdf' => $pdf, 'name' => $this->generatePdfFilename($quote)];
    }

    public function getRiderDetails($planId)
    {
        return LifeRiderOption::active()
            ->where('plan_id', $planId)
            ->select('id', 'rider_id', 'plan_id')
            ->with(['currencyCoverages' => function ($query) {
                $query->select('life_rider_option_id', 'min_cover', 'max_cover', 'currency_id');
            }])
            ->get();
    }

    public function quotePlans($data)
    {
        $quoteUuId = LifeQuote::where('uuid', '=', $data['quote_uuid'])->value('uuid');
        $planIds = $data['plan_ids'] ?? [];

        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-life-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        // Build query parameters
        $queryParams = [
            'quoteUID' => $quoteUuId,
            'isModified' => true,
            'isCqfOcb' => false,
        ];

        // Add planIds as comma-separated string if provided
        $queryParams['quoteUID'] = $quoteUuId;
        if (! empty($planIds)) {
            $queryParams['planIds'] = is_array($planIds) ? implode(',', $planIds) : $planIds;
        }

        $client = new \GuzzleHttp\Client;

        try {
            $kenRequest = $client->get(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'query' => $queryParams,
                    'timeout' => $plansApiTimeout,
                ]
            );

            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $kenRequest->getBody();

                return json_decode($getContents);
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'No Plans were found for the selected quote.';
            }

            return $responseBodyAsString;
        }
    }

    public function getCurrencyCoverages($planId)
    {
        return CurrencyCoverage::active()
            ->where('plan_id', $planId)
            ->select('plan_id', 'min_cover', 'max_cover', 'currency_id')
            ->with(['currency' => function ($query) {
                $query->select('id', 'code', 'text');
            }])
            ->get();
    }

    public function getRiders($planId)
    {
        return LifeRiderOption::active()
            ->where('plan_id', $planId)
            ->select('id', 'rider_id', 'plan_id', 'input_required')
            ->with(['currencyCoverages' => function ($query) {
                $query->select('life_rider_option_id', 'min_cover', 'max_cover', 'currency_id');
            }])
            ->with(['rider' => function ($query) {
                $query->select('id', 'text');
            }])
            ->get();
    }

    public function toggleLifePlanVisibility(array $data)
    {
        LoggerService::info('fn: toggleLifePlanVisibility', extra: [
            'data' => $data,
        ]);

        return app(KenService::class)->request('/toggle-life-plan-visibility', 'post', $data);
    }

    public function updateExchangeRate(string $quoteUID, $exchangeRate)
    {
        LoggerService::startQuoteLogging($quoteUID);

        LoggerService::info('fn: updateExchangeRate', extra: [
            'exchangeRate' => $exchangeRate,
        ]);

        $quote = LifeQuote::where('uuid', $quoteUID)->first();
        $quote->exchange_rate = $exchangeRate;
        if ($quote->save()) {
            LoggerService::info('fn: updateExchangeRate - Exchange rate updated successfully');
        } else {
            LoggerService::error('fn: updateExchangeRate - Failed to update exchange rate');
        }

        return $quote;
    }

    public function getLifeRiderInfo($rider, $plan)
    {
        if ($rider === null || ! ($rider->active ?? false)) {
            return 'Optional';
        }
        if (! ($rider->inputRequired ?? false)) {
            if (($rider->coverType ?? '') === 'VALUE') {
                foreach (($rider->criteria ?? []) as $criteria) {
                    if (($criteria->currency ?? null) === ($plan->currency ?? null) && isset($criteria->coverValue)) {
                        return is_string($criteria->coverValue)
                            ? "Covered {$criteria->coverValue}"
                            : 'Covered upto '.number_format($criteria->coverValue);
                    }
                }

                return 'Covered';
            } elseif (($rider->coverType ?? '') === 'COVER') {
                return is_string($plan->sumInsured ?? '')
                    ? "Covered {$plan->sumInsured}"
                    : 'Covered upto '.number_format($plan->sumInsured ?? 0);
            }
        } else {
            return is_string($rider->coverValue ?? '')
                ? "Covered {$rider->coverValue}"
                : 'Covered upto '.number_format($rider->coverValue ?? 0);
        }

        return 'Covered';
    }

    private function generatePdfFilename($quote): string
    {
        return 'InsuranceMarket.ae™ Life Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';
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
