<?php

namespace App\Services\Quotes;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\InvestmentFrequencyEnum;
use App\Enums\LookupsEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\CurrencyType;
use App\Models\InsuranceProviderPlan;
use App\Models\Lookup;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\RiderOption;
use App\Models\SavingsQuote;
use App\Services\BranchAssignmentService;
use App\Services\HttpRequestService;
use App\Services\KenService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavingsQuoteService extends BaseQuoteService
{
    protected $httpService;

    public function __construct(HttpRequestService $httpService)
    {
        parent::__construct(QuoteTypes::SAVINGS);
        $this->httpService = $httpService;
    }

    public function getData(bool $paginted = false, bool $forExport = false, bool $getTotalCount = false, bool $getQuery = false)
    {
        $query = $this->baseQuery()->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'advisor.primaryBranch',
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
            'savingsQuote',
            'savingsQuote.purpose',
            'savingsQuote.investmentFrequency',
            'savingsQuote.tenure',
            'nationality',
            'subSource:id,text',
            'branch:id,name',
        ])
            ->filter(forTotalLeadsCount: $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount)
            ->when(empty(request('booking_date')), function ($query) {
                $query->filterByCreatedAt(request('created_at_start'), request('created_at_end'));
            })
            ->when(request('investment_frequency'), function ($q) {
                $q->whereHas('savingsQuote', function ($sq) {
                    $sq->where('investment_criteria_id', request('investment_frequency'));
                });
            })
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date')
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByDateRange('booking_date', 'policy_booking_date');

        if (request()->has('debug') && request()->debug == 'true') {
            echo $query->toRawSql();
            exit;
        }

        if ($getQuery) {
            return $query;
        }

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function postProcessSavingsQuote($quotes)
    {
        return $quotes->map(function ($item) {
            $item->branch_name = ! $item->is_branch_applicable ? 'N/A' : ($item?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($item?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Savings));

            return $item;
        });
    }

    public function getFormOptions()
    {
        $lookUpData = $this->getSavingsQuoteLookUpData();

        return [
            'lookUpData' => $lookUpData,
            'nationalities' => Nationality::withActive()->options(),
            'genders' => GenderEnum::withLabels(),
        ];
    }

    public function create(array $data)
    {
        $sourceName = config('constants.SOURCE_NAME');
        $appUrl = config('constants.APP_URL');

        // Log sub-source parameters
        LoggerService::info('SavingsQuoteService create - Sub-source parameters', [
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
        ]);

        $data = [
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'dob' => $data['dob'],
            'nationalityId' => (int) $data['nationality_id'],
            'gender' => $data['gender'],
            'quoteTypeId' => (int) $this->quoteType->id(),
            'maritalStatusId' => (int) $data['marital_status_id'],
            'tenureId' => (int) $data['tenure_id'],
            'purposeId' => (int) $data['purpose_id'],
            'currencyId' => (int) $data['currency_id'],
            'investmentAmount' => (float) $data['investment_amount'],
            'investmentCriteriaId' => (int) $data['investment_frequency'],
            'additionalNotes' => $data['additional_notes'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'advisorId' => (! $this->hasRole(Auth::user(), RolesEnum::Admin)) ? Auth::id() : null,
            // Sub-source fields
            'subSourceId' => $data['sub_source_id'] ?? null,
            'subSourceOptionsId' => $data['sub_source_options_id'] ?? null,
        ];

        // Make API request to save the savings quote
        $response = Capi::request('/api/v1-save-savings-quote', 'post', $data);

        if (isset($response->quoteUID)) {
            $this->selfAssign(QuoteTypes::SAVINGS, $response->quoteUID, false);
        }

        return $response;
    }

    public function getOne(string $uuid, $allDetails = false)
    {
        return $this->baseQuery()->with([
            'savingsQuote',
            'savingsQuote.purpose',
            'savingsQuote.investmentFrequency',
            'savingsQuote.tenure',
            'subSource',
            'subSourceOption',
            'quoteCustomerPlan',
            'latestInsured',
            'latestInsured.insuredKyc',
            'branch:id,name',
            'customer',
            'passportVisaDetails',
        ])
            ->when($allDetails, function ($q) {
                $entityCustomerType = CustomerTypeEnum::Entity;
                $individualCustomerType = CustomerTypeEnum::Individual;

                $q->with([
                    'savingsQuote.currency',
                    'savingsQuote.maritalStatus',
                    'quoteStatus',
                    'currentlyInsuredWith',
                    'advisor',
                    'advisor.primaryBranch',
                    'paymentStatus',
                    'quoteDetail',
                    'quoteDetail.lostReason',
                    'renewalBatchModel',
                    'nationality',
                    'customer',
                    'customer.additionalContactInfo',
                    'insuranceProvider:id,text,code',
                    'insuranceProviderPlan',
                    'insuranceProvider',
                    'payments' => function ($q) {
                        $q->with([
                            'paymentStatus',
                            'personalPlan',
                            'paymentMethod',
                            'paymentStatusLogs',
                            'insuranceProvider',
                            'paymentSplits' => function ($q) {
                                $q->with([
                                    'paymentStatus',
                                    'paymentMethod',
                                    'documents',
                                    'verifiedByUser',
                                    'paymentCharges',
                                    'processJob',
                                ])
                                    ->orderBy('sr_no', 'asc');
                            },
                        ]);
                    },
                    'documents' => function ($q) {
                        $q->with('createdBy')->orderBy('created_at', 'desc');
                    },
                ])->select([
                    'personal_quotes.*',
                ])->selectRaw("
                IF(
                    EXISTS (
                        SELECT *
                        FROM quote_request_entity_mapping
                        WHERE quote_type_id = {$this->quoteType->id()}
                        AND quote_request_id = personal_quotes.id
                    ), '{$entityCustomerType}', '{$individualCustomerType}'
                ) AS customer_type
            ");
            })
            ->where('uuid', $uuid)->firstOrFail();
    }

    public function update($uuid, $data)
    {
        $isAnyFieldChanged = false;
        $quote = null;

        LoggerService::startQuoteLogging($uuid);

        [$quote, $isAnyFieldChanged] = DB::transaction(function () use ($uuid, $data) {
            $isAnyFieldChanged = false;

            $quote = $this->baseQuery()->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id', 'gender',
                'sub_source_id', 'sub_source_options_id', 'additional_notes',
            ]);
            $quoteData['updated_by_id'] = Auth::id();
            LoggerService::info('updateSavingsQuote: ', $quoteData);
            $quote->update($quoteData);

            if ($quote->savingsQuote) {
                LoggerService::info('fn: updateSavingsQuote - Savings Quote Found');

                $fieldsToRevisePlans = [
                    'dob',
                    'nationality_id',
                    'gender',
                    'tenure_id',
                    'currency_id',
                    'investment_amount',
                    'investment_criteria_id',
                    'purpose_id',
                    'marital_status_id',
                ];

                $savingsQuote = $quote->savingsQuote;
                foreach ($fieldsToRevisePlans as $field) {
                    if (isset($data[$field]) && $data[$field] != $savingsQuote->$field) {
                        $isAnyFieldChanged = true;
                        break;
                    }
                }

                $savingsQuoteData = Arr::only($data, app(SavingsQuote::class)->getFillable());
                $quote->savingsQuote->fill($savingsQuoteData);
                $quote->savingsQuote->save();
            } else {
                LoggerService::info('fn: updateSavingsQuote - Savings Quote Not Found, Creating New One');

                $savingsQuoteData = Arr::only($data, app(SavingsQuote::class)->getFillable());
                $savingsQuote = $quote->savingsQuote()->create($savingsQuoteData);
            }

            return [$quote, $isAnyFieldChanged];
        });

        if ($isAnyFieldChanged) {
            $this->getQuotePlans($uuid, true);
            LoggerService::info('fn: updateSavingsQuote - Fields Changed, Plans to be revised');
        }

        return $quote;
    }

    public function getShowData(string $uuid)
    {
        $quote = $this->getOne($uuid, true);
        $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Savings));
        $data = $this->getShowCommonData($quote);

        $data['permissions']['canEditQuote'] = ($this->can(Auth::user(), PermissionsEnum::SAVINGS_QUOTES_EDIT) || (userHasProduct(quoteTypeCode::SAVINGS) && $this->can(Auth::user(), PermissionsEnum::VIEW_ALL_LEADS)));

        // Get lookups for CreatePlan and PlanDetails dropdowns
        $lookUpData = $this->getSavingsQuoteLookUpData();
        $localLookups = $this->getLocalLookups();

        $eligiblePlanCodesRaw = (string) getAppStorageValueByKey(
            ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES,
            default: '',
            useCache: true
        );
        $passportEligiblePlanCodes = array_values(array_filter(
            array_map('trim', explode(',', $eligiblePlanCodesRaw)),
            static fn (string $code): bool => $code !== ''
        ));

        return [
            'canAddBatchNumber' => $this->hasRole(Auth::user(), RolesEnum::SavingsManager),
            'ocrEligiblePlanCodes' => [
                OCRDocumentTypeEnum::PASSPORT->value => $passportEligiblePlanCodes,
            ],
            'ecomSavingsInsuranceQuoteUrl' => config('constants.ECOM_SAVINGS_INSURANCE_QUOTE_URL'),
            'lookUpData' => $lookUpData,
            'localLookups' => $localLookups,
            ...$data,
        ];
    }

    public function getSavingsQuoteLookUpData()
    {
        return app(LookupService::class)->getSavingsQuoteLookUpData();
    }

    /**
     * Get local savings lookups from database for CreatePlan and PlanDetails dropdowns
     */
    public function getLocalLookups(): array
    {
        // Plan types from lookups table
        $planTypes = Lookup::where('key', 'plan-type')
            ->where('quote_type_id', QuoteTypeId::Savings)
            ->where('is_active', 1)
            ->select('id', 'code', 'text')
            ->get();

        // Insurance provider plans from insurance_provider_plans table
        $providerPlans = InsuranceProviderPlan::where('quote_type_id', QuoteTypeId::Savings)
            ->active()
            ->with(['eligibilities', 'currencyCoverages.currency', 'riders'])
            ->get();

        // Investment frequencies from lookups table
        $investmentFrequencies = Lookup::where('key', LookupsEnum::INVESTMENT_TYPE)
            ->where('is_active', 1)
            ->select('id', 'code', 'text')
            ->get();

        // Currencies from currency_type table
        $currencies = CurrencyType::withActive()
            ->select('id', 'code', 'text')
            ->get();

        // Payment terms (static values based on investment frequency)
        $paymentTerms = collect([
            ['id' => 'monthly', 'code' => 'monthly', 'text' => 'Monthly', 'value' => 12],
            ['id' => 'quarterly', 'code' => 'quarterly', 'text' => 'Quarterly', 'value' => 3],
            ['id' => 'semi_annually', 'code' => 'semi_annually', 'text' => 'Semi-Annually', 'value' => 2],
            ['id' => 'annually', 'code' => 'annually', 'text' => 'Annually', 'value' => 1],
            ['id' => 'single_payment', 'code' => 'single_payment', 'text' => 'Single Payment', 'value' => 0],
        ]);

        return [
            'planTypes' => $planTypes,
            'providerPlans' => $providerPlans,
            'investmentFrequencies' => $investmentFrequencies,
            'currencies' => $currencies,
            'paymentTerms' => $paymentTerms,
        ];
    }

    public function getInvestmentFrequencies()
    {
        return collect($this->getSavingsQuoteLookUpData()->savingsInvestmentType ?? [])->map(function ($item) {
            return ['value' => $item['id'], 'label' => $item['text']];
        })->toArray();
    }

    public function getAvailablePlans($uuid)
    {
        return $this->listQuotePlans($uuid);
    }

    public function listQuotePlans($id)
    {
        $listQuotePlans = '';
        $quotePlans = $this->getQuotePlans($id);
        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string') {
                $listQuotePlans = $quotePlans->quotes->plans;
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $listQuotePlans;
    }

    public function getQuotePlans(string $uuid, bool $getLatestRating = false)
    {
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-savings-quote-plans';
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

        $client = new Client;

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
                $getdecodeContents = json_decode($getContents);

                return $getdecodeContents;

            }
        } catch (BadResponseException $e) {
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
                $responseBodyAsString = 'Quote unavailable for the selected location and region. Please call 800 ALFRED.';
            }

            return $responseBodyAsString;
        }
    }

    public function savingsPlanModify($request)
    {
        if (($response = $this->isPlanModifyAllowed($request->all())) === true) {
            $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-savings-quote-plan';
            $apiToken = config('constants.KEN_API_TOKEN');
            $apiTimeout = config('constants.KEN_API_TIMEOUT');
            $apiUserName = config('constants.KEN_API_USER');
            $apiPassword = config('constants.KEN_API_PWD');

            $savingsPlanData = [
                'quoteUID' => $request->quote_uuid,
                'update' => true, // Always true for savings plan updates
                'url' => strval($request->current_url ?? ''),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->header('User-Agent'),
                'userId' => strval(Auth::id()),
                'plans' => [
                    [
                        'planId' => (int) $request->plan_id,
                        'actualPremium' => (float) ($request->actual_premium ?? 0),
                        'isDisabled' => (bool) ($request->is_disabled ?? false),
                        'insurerQuoteNo' => strval($request->insurer_quote_no ?? ''),
                        'isManualUpdate' => (bool) ($request->is_manual_update ?? false),
                    ],
                ],
            ];

            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            return $this->httpService->processRequest($savingsPlanData, $apiCreds);
        }

        return $response;
    }

    /**
     * Toggle visibility for multiple savings plans (Show/Hide)
     *
     * @param  Request  $request
     * @return int|string
     */
    public function updateManualPlansBulk($request)
    {
        if ($request->planIds && isset($request->toggle) && isset($request->quote_uuid)) {
            $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-savings-quote-plan';
            $apiToken = config('constants.KEN_API_TOKEN');
            $apiTimeout = config('constants.KEN_API_TIMEOUT');
            $apiUserName = config('constants.KEN_API_USER');
            $apiPassword = config('constants.KEN_API_PWD');

            $isDisabled = filter_var($request->toggle, FILTER_VALIDATE_BOOLEAN);
            $plansArray = [];

            foreach ($request->planIds as $planId) {
                $plansArray[] = [
                    'planId' => (int) $planId,
                    'isDisabled' => $isDisabled,
                ];
            }

            $dataArray = [
                'quoteUID' => $request->quote_uuid,
                'update' => true,
                'plans' => $plansArray,
            ];

            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            return $this->httpService->processRequest($dataArray, $apiCreds);
        }

        return 'Invalid request parameters';
    }

    public function isPlanModifyAllowed($data)
    {
        $logPrefix = self::class.' fn: isPlanModifyAllowed ';
        $quote = PersonalQuote::where('uuid', $data['quote_uuid'])->with('paymentStatus')->first();
        LoggerService::startQuoteLogging($quote);

        $isAllowed = false;

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            $savingsPayment = Payment::where('code', '=', $quote->code)->first();
            if (! empty($savingsPayment->captured_at)) {
                $paymentCapturedAt = $savingsPayment->captured_at;
                $today = Carbon::today();

                $dateLimitForAdvisor = Carbon::parse($paymentCapturedAt)->addDays(6);
                $dateLimitForManager = Carbon::parse($dateLimitForAdvisor)->addDays(6);

                if ($this->hasRole(Auth::user(), RolesEnum::SavingsAdvisor) && $today->lte($dateLimitForAdvisor)) {
                    info($logPrefix.' plan modify allowed to advisor and captured days diff is '.$paymentCapturedAt);
                    $isAllowed = true;
                } elseif ($this->hasRole(Auth::user(), RolesEnum::SavingsManager) && $today->gt($dateLimitForAdvisor) && $today->lte($dateLimitForManager)) {
                    info($logPrefix.' plan modify allowed to savings manager and captured days diff is '.$paymentCapturedAt);
                    $isAllowed = true;
                }
            }
        }

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED]) && $this->hasAnyRole(Auth::user(), [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager])) {
            info($logPrefix.' plan modify allowed to advisor');
            $isAllowed = true;
        }

        if (
            empty($quote->payment_status_id) ||
            (in_array($quote->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PENDING, PaymentStatusEnum::FAILED, PaymentStatusEnum::DECLINED, PaymentStatusEnum::DRAFT]) &&
                $this->hasAnyRole(Auth::user(), [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager]))
        ) {
            info($logPrefix.' plan modify allowed');
            $isAllowed = true;
        }

        if (! $isAllowed) {
            info($logPrefix.' plan modification is not allowed');

            return 'Plan Modification is not allowed';
        }
        LoggerService::endLogging();

        return true;
    }

    public function getPlanDetails($quoteId, $planId)
    {
        $quotePlans = $this->getQuotePlans($quoteId);

        // Check if there was an error retrieving plans
        if (gettype($quotePlans) == 'string') {
            return $this->createErrorResponse($quotePlans);
        }

        if (! isset($quotePlans->quotes->plans)) {
            return $this->createErrorResponse('No plans available');
        }

        $planResult = $this->findPlanById($quotePlans->quotes->plans, $planId);

        if ($planResult['found'] === false) {
            return $this->createErrorResponse('Plan not found');
        }

        $foundPlan = $planResult['plan'];
        $planSource = $planResult['source'];

        // Determine investment frequency based on the plan source
        $investmentFrequency = match ($planSource) {
            'regular' => InvestmentFrequencyEnum::REGULAR->value,
            'lumpsum' => InvestmentFrequencyEnum::LUMPSUM->value,
            default => InvestmentFrequencyEnum::REGULAR->value
        };

        // Extract eligibility values
        $eligibility = $foundPlan->eligibilities ?? [];
        $minimumInvestment = $this->getEligibilityValue($eligibility, 'minimumInvestmentAmount');
        $policyTerm = $this->getEligibilityValue($eligibility, 'policyTerm');

        return [
            'error' => false,
            'data' => $this->formatPlanData($foundPlan, $investmentFrequency, $minimumInvestment, $policyTerm),
            'status' => 200,
        ];
    }

    private function createErrorResponse($message)
    {
        return [
            'error' => true,
            'message' => $message,
            'status' => 404,
        ];
    }

    private function findPlanById($plans, $planId)
    {
        // Check regular plans
        if (isset($plans->regular)) {
            foreach ($plans->regular as $plan) {
                if ($plan->id == $planId) {
                    return ['found' => true, 'plan' => $plan, 'source' => 'regular'];
                }
            }
        }

        // Check lumpsum plans
        if (isset($plans->lumpsum)) {
            foreach ($plans->lumpsum as $plan) {
                if ($plan->id == $planId) {
                    return ['found' => true, 'plan' => $plan, 'source' => 'lumpsum'];
                }
            }
        }

        // Check if plans is an array (different structure)
        if (is_array($plans)) {
            foreach ($plans as $plan) {
                if ($plan->id == $planId) {
                    return ['found' => true, 'plan' => $plan, 'source' => 'regular'];
                }
            }
        }

        return ['found' => false];
    }

    private function formatPlanData($plan, $investmentFrequency, $minimumInvestment, $policyTerm)
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name ?? '',
            'providerCode' => $plan->providerCode ?? '',
            'providerName' => $plan->providerName ?? '',
            'planTypeId' => $plan->planTypeId ?? null,
            'investmentFrequency' => ucfirst($investmentFrequency),
            'currency' => 'USD',
            'minimumInvestment' => $minimumInvestment,
            'policyTerm' => $policyTerm,
            'eligibilities' => $plan->eligibilities ?? [],
            'includedBenefits' => $plan->includedBenefits ?? [],
            'keyFeatureDocument' => $plan->keyFeatureDocument ?? [],
            'description' => $plan->description ?? '',
            'policyWordings' => $plan->policyWordings ?? [],
            'fundDetails' => $plan->fundDetails ?? [],
            'actualPremium' => $plan->actualPremium ?? 0,
            'insurerQuoteNo' => $plan->insurerQuoteNo ?? '',
            'isDisabled' => $plan->isDisabled ?? false,
            'isManualUpdate' => $plan->isManualUpdate ?? false,
            'instantPolicy' => (bool) ($plan->instantPolicy ?? false),
        ];
    }

    private function getEligibilityValue($eligibility, $code)
    {
        if (! is_array($eligibility)) {
            return 'N/A';
        }

        $found = collect($eligibility)->firstWhere('code', $code);

        return $found ? $found->value : 'N/A';
    }

    /**
     * Call KEN to fetch savings provider plan data (e.g. lump sum for Purple Investment).
     *
     * @param  array<string, mixed>  $payload  Body for /fetch-savings-provider-plan (quoteUID is set by the controller).
     * @return array{success: bool, data?: mixed, message?: string, status?: int}
     */
    public function fetchSavingsProviderPlan(array $payload): array
    {
        LoggerService::info('SavingsQuoteService - fetchSavingsProviderPlan', [
            'quoteUID' => $payload['quoteUID'] ?? null,
            'planId' => $payload['planId'] ?? null,
        ]);

        try {
            $response = app(KenService::class)->sendRequest('/fetch-savings-provider-plan', 'post', $payload);
        } catch (ConnectionException $e) {
            LoggerService::error('SavingsQuoteService - fetchSavingsProviderPlan connection failed', exception: $e);

            return [
                'success' => false,
                'message' => 'Unable to reach the savings provider service. Please try again later.',
                'status' => 503,
            ];
        }

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        $json = $response->json();
        $message = 'Failed to fetch savings provider plan';
        if (is_array($json)) {
            $message = $json['message'] ?? $json['msg'] ?? $json['error'] ?? $message;
            if (! is_string($message)) {
                $message = 'Failed to fetch savings provider plan';
            }
        }

        return [
            'success' => false,
            'message' => $message,
            'status' => $response->status(),
        ];
    }

    /**
     * Process savings plan (create or update) - handles new payload structure
     */
    public function processSavingsPlan(array $payload, string $quoteUuId)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-savings-quote-plan';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');
        $apiUserName = config('constants.KEN_API_USER');
        $apiPassword = config('constants.KEN_API_PWD');

        // Build the payload for Ken API - always use route-validated quoteUuId to prevent IDOR
        $savingsPlanData = [
            'quoteUID' => $quoteUuId,
            'update' => $payload['update'] ?? false,
            'plans' => [],
        ];

        // Process each plan in the payload
        foreach ($payload['plans'] ?? [] as $plan) {
            $processedPlan = [
                'actualPremium' => (float) ($plan['actualPremium'] ?? 0),
                'discountPremium' => (float) ($plan['discountPremium'] ?? 0),
                'planId' => (int) ($plan['planId'] ?? 0),
                'isDisabled' => (bool) ($plan['isDisabled'] ?? false),
                'isManualUpdate' => (bool) ($plan['isManualUpdate'] ?? true),
                'insurerQuoteNo' => strval($plan['insurerQuoteNo'] ?? ''),
                'investmentAmount' => (float) ($plan['investmentAmount'] ?? 0),
                'currency' => strval($plan['currency'] ?? 'AED'),
                'currencyId' => (int) ($plan['currencyId'] ?? 0),
                'paymentTerm' => (int) ($plan['paymentTerm'] ?? 0),
                'tenure' => (int) ($plan['tenure'] ?? 0),
                'tenureId' => isset($plan['tenureId']) ? (int) $plan['tenureId'] : null,
                'ror' => (float) ($plan['ror'] ?? 0),
                'investmentFrequency' => strval($plan['investmentFrequency'] ?? 'Regular'),
                'investmentFrequencyId' => isset($plan['investmentFrequencyId']) ? (int) $plan['investmentFrequencyId'] : null,
                'lumpSumPayout' => isset($plan['lumpSumPayout']) ? (float) $plan['lumpSumPayout'] : null,
            ];

            // Process riders if present
            if (isset($plan['riders']) && is_array($plan['riders'])) {
                $processedPlan['riders'] = array_map(function ($rider) {
                    return [
                        'riderId' => (int) ($rider['riderId'] ?? 0),
                        'active' => (bool) ($rider['active'] ?? false),
                        'price' => isset($rider['price']) ? (float) $rider['price'] : 0,
                        'coverValue' => isset($rider['coverValue']) ? (float) $rider['coverValue'] : 0,
                    ];
                }, $plan['riders']);
            }

            $savingsPlanData['plans'][] = $processedPlan;
        }

        $apiCreds = [
            'apiEndPoint' => $apiEndPoint,
            'apiToken' => $apiToken,
            'apiTimeout' => $apiTimeout,
            'apiUserName' => $apiUserName,
            'apiPassword' => $apiPassword,
        ];

        LoggerService::info('SavingsQuoteService - processSavingsPlan', [
            'quote_uuid' => $quoteUuId,
            'update' => $savingsPlanData['update'],
            'plansData' => $savingsPlanData['plans'] ?? [],
            'url' => strval(request()->url()),
            'ipAddress' => request()->ip(),
            'userAgent' => request()->header('User-Agent'),
            'userId' => strval(Auth::id()),
        ]);

        return $this->httpService->processRequest($savingsPlanData, $apiCreds);
    }

    /**
     * Create a new savings plan manually (deprecated - use processSavingsPlan)
     *
     * @deprecated Use processSavingsPlan instead
     */
    public function createSavingsPlan($request, $quoteUuId)
    {
        // Legacy support - convert old format to new format
        $payload = [
            'quoteUID' => $quoteUuId,
            'update' => false,
            'plans' => [
                [
                    'planId' => $request->savings_plan_id,
                    'investmentAmount' => $request->actual_premium ?? 0,
                    'currency' => 'AED',
                    'currencyId' => 2,
                    'paymentTerm' => 1,
                    'tenure' => 10,
                    'ror' => 0,
                    'investmentFrequency' => 'Regular',
                    'isDisabled' => false,
                    'isManualUpdate' => true,
                    'insurerQuoteNo' => $request->insurer_quote_no ?? '',
                ],
            ],
        ];

        return $this->processSavingsPlan($payload, $quoteUuId);
    }

    /**
     * Get provider plans from database (like Life)
     */
    public function getProviderPlans($providerId)
    {
        return InsuranceProviderPlan::where(['provider_id' => $providerId, 'quote_type_id' => QuoteTypeId::Savings])
            ->active()
            ->with(['eligibilities', 'currencyCoverages.currency'])
            ->get();
    }

    /**
     * Get riders for a plan (like Life)
     */
    public function getRiders($planId)
    {
        return RiderOption::where('plan_id', $planId)
            ->active()
            ->whereHas('rider', fn ($q) => $q->active())
            ->select('id', 'rider_id', 'plan_id', 'input_required', 'input_type', 'max_age', 'cover_type')
            ->with(['rider' => fn ($q) => $q->select('id', 'text', 'code')->active()])
            ->get();
    }

    /**
     * Toggle savings plan visibility (hide/show)
     *
     * @return mixed
     */
    public function toggleSavingsPlanVisibility(array $data)
    {
        LoggerService::info('fn: toggleSavingsPlanVisibility', extra: [
            'data' => $data,
        ]);

        // If providerId is not provided, try to get it from the quote
        if (empty($data['providerId']) && ! empty($data['quoteUID'])) {
            $quote = PersonalQuote::where('uuid', $data['quoteUID'])->first();
            if ($quote && $quote->insurance_provider_id) {
                $data['providerId'] = $quote->insurance_provider_id;
            }
        }

        return app(KenService::class)->request('/toggle-savings-plan-visibility', 'post', $data);
    }

    public function updateExchangeRate(string $quoteUID, $exchangeRate)
    {
        LoggerService::startQuoteLogging($quoteUID);

        LoggerService::info('fn: updateExchangeRate', extra: [
            'exchangeRate' => $exchangeRate,
        ]);

        $quote = SavingsQuote::where('uuid', $quoteUID)->first();

        if (! $quote) {
            LoggerService::error('fn: updateExchangeRate - Quote not found', extra: ['quoteUID' => $quoteUID]);

            return null;
        }

        $quote->exchange_rate = $exchangeRate;
        if ($quote->save()) {
            LoggerService::info('fn: updateExchangeRate - Exchange rate updated successfully');
        } else {
            LoggerService::error('fn: updateExchangeRate - Failed to update exchange rate');
        }

        return $quote;
    }
}
