<?php

namespace App\Services\Quotes;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\SavingsQuote;
use App\Services\LookupService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Services\HttpRequestService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use App\Models\QuoteCustomerPlan;

class SavingsQuoteService extends BaseQuoteService
{
    protected $httpService;

    public function __construct(HttpRequestService $httpService)
    {
        parent::__construct(QuoteTypes::SAVINGS);
        $this->httpService = $httpService;
    }

    public function getData(bool $paginted = false, bool $forExport = false, bool $getTotalCount = false)
    {
        $query = $this->baseQuery()->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
            'savingsQuote',
            'savingsQuote.purpose',
            'savingsQuote.investmentFrequency',
            'savingsQuote.tenure',
            'nationality',
        ])
            ->filter(forTotalLeadsCount: $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount)
            ->filterByCreatedAt(request('created_at_start'), request('created_at_end'))
            ->when(request('investment_frequency'), function ($q) {
                $q->whereHas('savingsQuote', function ($sq) {
                    $sq->whereHas('investmentFrequency', function ($iq) {
                        $iq->where('code', request('investment_frequency'));
                    });
                });
            });

        $this->adjustQueryByInsurerInvoiceFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function getFormOptions()
    {
        $lookUpData = $this->getSavingsQuoteLookUpData();

        return [
            'lookUpData' => $lookUpData,
            'genders' => GenderEnum::withLabels(),
        ];
    }

    public function create(array $data)
    {
        $sourceName = config('constants.SOURCE_NAME');
        $appUrl = config('constants.APP_URL');

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
            'nicotineStatus' => (int) $data['nicotine_status'],
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
        ];

        // Make API request to save the savings quote
        $response = Capi::request('/api/v1-save-savings-quote', 'post', $data);

        if (isset($response->quoteUID)) {
            $this->selfAssign(QuoteTypes::SAVINGS, $response->quoteUID);
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
                    'paymentStatus',
                    'quoteDetail',
                    'quoteDetail.lostReason',
                    'renewalBatchModel',
                    'nationality',
                    'customer',
                    'customer.additionalContactInfo',
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
                                ])
                                    ->orderBy('sr_no', 'asc');
                            },
                        ]);
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

    public function update(string $uuid, array $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->baseQuery()->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id', 'gender',
            ]);

            $quoteData['updated_by_id'] = Auth::id();
            $quote->update($quoteData);

            $quote->savingsQuote()->updateOrCreate(
                ['personal_quote_id' => $quote->id],
                $data
            );

            return $quote;
        });
    }

    public function getShowData(string $uuid)
    {
        $quote = $this->getOne($uuid, true);
        $data = $this->getShowCommonData($quote);

        return [
            'canAddBatchNumber' => $this->hasRole(Auth::user(), RolesEnum::SavingsManager),
            'selectedCustomerPlans' => $this->getSelectedCustomerPlans($uuid),
            ...$data,
        ];
    }

    public function getSelectedCustomerPlans(string $uuid)
    {
        return QuoteCustomerPlan::byQuoteUuid($uuid)
            ->byQuoteType($this->quoteType->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'provider_name' => $plan->provider_name,
                ];
            });
    }

    public function getSavingsQuoteLookUpData()
    {
        return app(LookupService::class)->getSavingsQuoteLookUpData();
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

    public function getQuotePlans($id, $extraData = [])
    {
        $quoteUuId = SavingsQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-savings-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'lang' => 'en',
            ...$extraData,
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
                $getdecodeContents = json_decode($getContents);

                return $getdecodeContents;

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
}
