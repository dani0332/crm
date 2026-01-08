<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BranchAssignmentService;
use App\Services\CustomerInsuredService;
use App\Services\LookupService;
use App\Services\SplitPaymentService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CyberQuoteService extends BaseQuoteService
{
    private const HAPPINESS_SUPPORT_USER_EMAIL = 'hapexuser@gmail.com';

    public function __construct(
        private CustomerInsuredService $customerInsuredService
    ) {
        parent::__construct(QuoteTypes::CYBER);
    }

    public function getData(bool $paginted = false, bool $forExport = false, bool $getTotalCount = false)
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
            'nationality',
            'insuranceProviderPlan',
            'cyberQuote.coverage',
            'branch:id,name',
        ])
            ->filter(forTotalLeadsCount: $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount)
            ->filterByCreatedAt(request('created_at_start'), request('created_at_end'))
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date')
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByDateRange('booking_date', 'policy_booking_date')
            ->filterBy('payment_status_id')
            ->filterBy('is_ecommerce', isBool: true)
            ->filterIn('insurer_aml_status')
            ->filterIn('plan_name', 'plan_id')
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at')
            ->when(request()->filled('coverage_up_to'), function ($q) {
                $q->whereHas('cyberQuote', function ($subQuery) {
                    $subQuery->where('coverage_id', request('coverage_up_to'));
                });
            })
            ->when(request()->filled('insurer_tax_invoice_number'), function ($q) {
                $q->whereHas('payments', function ($subQuery) {
                    $subQuery->where('insurer_tax_number', request('insurer_tax_invoice_number'));
                });
            })
            ->when(request()->filled('insurer_commission_tax_invoice_number'), function ($q) {
                $q->whereHas('payments', function ($subQuery) {
                    $subQuery->where('insurer_commmission_invoice_number', request('insurer_commission_tax_invoice_number'));
                });
            });

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        if (request()->has('debug') && request()->debug == 'true') {
            echo $query->toRawSql();
            exit;
        }

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function postProcessCyberQuotes($quotes)
    {
        $quotes->getCollection()->transform(function ($item) {
            $item->branch_name = ! $item->is_branch_applicable
                ? 'N/A'
                : ($item?->branch?->name ?? app(BranchAssignmentService::class)
                    ->getBranchName($item?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Cyber));

            return $item;
        });

        return $quotes;
    }

    public function getOne(string $uuid, $allDetails = false)
    {
        $quote = $this->baseQuery()
            ->with('cyberQuote')
            ->when($allDetails, function ($q) {
                $entityCustomerType = CustomerTypeEnum::Entity;
                $individualCustomerType = CustomerTypeEnum::Individual;

                $q->with([
                    'quoteStatus',
                    'currentlyInsuredWith',
                    'advisor',
                    'advisor.primaryBranch',
                    'branch:id,name',
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
                    'latestInsured' => function ($q) {
                        $q->where('customer_insured.quote_type_id', QuoteTypeId::Cyber);
                    },
                    'latestInsured.insuredKyc:id,insured_id',
                    'cyberQuote.coverage',
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

        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;
        if (isset($data['latestInsured'])) {
            $quote->emirates_id_number = $data['latestInsured']['id_type'] == 'emiratesId' ? $data['latestInsured']['id_number'] : null;
        }

        $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Cyber));

        return $quote;
    }

    public function getShowData(string $uuid)
    {
        $quote = $this->getOne($uuid, true);

        // Map payment status text similar to other LOBs
        $quote->payment_status_id_text = app(SplitPaymentService::class)->mapQuotePaymentStatus(
            $quote->payment_status_id,
            $quote->payment_status_id_text ?? $quote->paymentStatus?->text ?? null
        );

        // Replace advisor name with "Auto Issued" if advisor is automation user
        // Check test mode for UAT/Staging vs Production email
        $automationUserEmail = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE, useCache: true) == '1'
            ? self::HAPPINESS_SUPPORT_USER_EMAIL // Test/UAT email
            : PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL; // Production email

        if ($quote->advisor && $quote->advisor->email === $automationUserEmail) {
            $quote->advisor->name = PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_LABEL;
        }

        $data = $this->getShowCommonData($quote);

        // Generate plan-specific URL if plan and provider are available
        $planURL = $this->generatePlanURL($quote);
        if ($planURL) {
            $data['planURL'] = $planURL;
        }

        $data['permissions']['canEditQuote'] = ($this->can(Auth::user(), PermissionsEnum::CYBER_QUOTES_EDIT) || (userHasProduct(quoteTypeCode::CYBER) && $this->can(Auth::user(), PermissionsEnum::VIEW_ALL_LEADS)));

        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return [
            'canAddBatchNumber' => $this->hasRole(Auth::user(), RolesEnum::CyberManager),
            'insuredDetails' => $this->customerInsuredService->getInsuredDetails($quote->id),
            'amlStatusName' => $amlStatusName,
            ...$data,
        ];
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
                $listQuotePlans = $quotePlans->quotes->plans ?? [];
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $listQuotePlans;
    }

    public function getQuotePlans($id, bool $getLatestRating = false)
    {
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/cyber/get-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $id,
            'lang' => 'en',
            'getLatestRating' => $getLatestRating,
            'callSource' => strtolower(LeadSourceEnum::IMCRM),
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
                $getContents = (string) $kenRequest->getBody();
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
            } else {
                $responseBodyAsString = $contents;
            }

            return $responseBodyAsString;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $responseBodyAsString = 'Connection error occurred.';

            return $responseBodyAsString;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $responseBodyAsString = 'Request error occurred.';

            return $responseBodyAsString;
        } catch (\Exception $e) {
            $responseBodyAsString = 'An unexpected error occurred.';

            return $responseBodyAsString;
        }

        return 'Failed to fetch quote plans.';
    }

    public function getFormOptions()
    {
        $lookUpData = app(LookupService::class)->getCyberQuoteLookUpData();

        return [
            'lookUpData' => $lookUpData,
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
            'emirateOfRegistrationId' => (int) $data['emirate_of_registration_id'],
            'quoteTypeId' => (int) $this->quoteType->id(),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'advisorId' => (! $this->hasRole(Auth::user(), RolesEnum::Admin)) ? Auth::id() : null,
        ];

        // Make API request to save the savings quote
        $response = Capi::request('/api/cyber/create', 'post', $data);

        if (isset($response->quoteUID)) {
            $this->selfAssign(QuoteTypes::CYBER, $response->quoteUID, true);
        }

        return $response;
    }

    public function update(string $uuid, array $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->baseQuery()->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id',
            ]);

            $quoteData['updated_by_id'] = Auth::id();
            $quote->update($quoteData);

            $quote->cyberQuote()->updateOrCreate(
                ['personal_quote_id' => $quote->id],
                Arr::only($data, ['emirate_of_registration_id'])
            );

            return $quote;
        });
    }

    public function getCyberCoverages()
    {
        return app(LookupService::class)->getCyberCoverages();
    }

    private function generatePlanURL($quote): ?string
    {
        $plan = $quote->plan_id ? $quote->insuranceProviderPlan : null;
        $insuranceProvider = $quote->insurance_provider_id ? $quote->insuranceProvider : null;

        if ($plan && $insuranceProvider && $insuranceProvider->code) {
            $planForLink = (object) [
                'id' => $plan->id,
                'providerCode' => $insuranceProvider->code,
            ];

            return $this->getEcomQuoteLink($this->quoteType, $quote->uuid, $planForLink);
        }

        // Return null - base URL from getShowCommonData will be used
        return null;
    }

    public function applyAutomationFailureNotificationRules(
        PersonalQuote $quote,
        array $cc,
        string $processInvolved,
        ?string $recipientEmail,
        ?string $recipientName
    ): array {
        if ((int) $quote->quote_type_id !== QuoteTypeId::Cyber) {
            return [
                'recipientEmail' => $recipientEmail,
                'recipientName' => $recipientName,
                'processInvolved' => $processInvolved,
            ];
        }

        $distribution = $this->getCyberDistributionEmails();
        $isCaptureFailure = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_PAYMENT_CAPTURE;

        if ($isCaptureFailure) {
            [$recipientEmail, $recipientName] = $this->getPaContactDetails();
        } elseif (! $recipientEmail && $quote?->advisor) {
            $recipientEmail = $quote->advisor->email;
            $recipientName = $quote->advisor->name;
        }

        if (! $recipientEmail) {
            // this is additional check to get pa contact details if recipient email is not set and this situation can be use for OE user which is not updated by BA yet 26-dec-2025
            [$recipientEmail, $recipientName] = $this->getPaContactDetails();
        }

        // adding advisor email to cc if it is not in recipient email this happened on capture failure situation
        if ($quote?->advisor?->email && $quote->advisor->email !== $recipientEmail) {
            $distribution[] = $quote->advisor->email;
        }

        $distribution = array_values(array_unique(array_filter($distribution)));
        if (! empty($distribution)) {
            $cc = $distribution;
        }

        return [
            'cc' => $cc,
            'recipientEmail' => $recipientEmail,
            'recipientName' => $recipientName,
            'processInvolved' => PolicyIssuanceEnum::mapProcessTextForAutomationFailureNotification($processInvolved),
        ];
    }

    private function getCyberDistributionEmails(): array
    {
        $configured = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_FAILURE_EMAIL, useCache: true);

        if (empty($configured)) {
            return [];
        }

        $emails = array_map('trim', explode(',', $configured));
        $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        return ! empty($emails) ? array_values($emails) : [];
    }

    private function getPaContactDetails(): array
    {
        if (! app()->environment('production')) {
            $email = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_BOOKING_TEAM_EMAIL_TEST, useCache: true);
            $name = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_BOOKING_TEAM_NAME_TEST, useCache: true);

            return [$email, $name];
        }

        $email = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_BOOKING_TEAM_EMAIL, useCache: true);
        $name = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_BOOKING_TEAM_NAME, useCache: true);

        return [$email, $name];
    }

    private function getAdvisorManagerEmail(PersonalQuote $quote): ?string
    {
        if (! $quote?->advisor) {
            return null;
        }

        $cyberManager = $quote->advisor
            ->managers()
            ->get()
            ->first(function (User $manager) {
                return $manager->isCyberManager();
            });

        return $cyberManager?->email;
    }

    /**
     * Get customer cyber info for AML screening automation.
     *
     * @return object|false
     */
    public function getCustomerCyberInfo(int $quoteRequestId, string $quoteType)
    {
        $model = $this->getModelObject($quoteType);

        if (! class_exists($model)) {
            return false;
        }

        $customerCyberInfo = DB::table('personal_quotes as pq')
            ->leftJoin('customer_insured as ci', function ($join) {
                $join->on('ci.quote_request_id', '=', 'pq.id')
                    ->where('ci.quote_type_id', '=', QuoteTypeId::Cyber);
            })
            ->leftJoin('insured as i', 'ci.insured_id', '=', 'i.id')
            ->select(
                'pq.id',
                'pq.code',
                'pq.customer_id',
                'pq.gender',
                'pq.first_name',
                'pq.last_name',
                'pq.dob',
                'pq.nationality_id',
                'i.id_type',
                'i.id_number'
            )
            ->where('pq.id', $quoteRequestId)
            ->where('pq.quote_type_id', QuoteTypeId::Cyber)
            ->orderBy('ci.updated_at', 'desc')
            ->first();

        return $customerCyberInfo;
    }

    /**
     * Check if customer cyber info is complete for AML screening.
     */
    public function checkCustomerCyberInfoIsComplete(array $cyberQuoteRequest): array
    {
        $message = '';
        $requiredProperty = collect(['first_name', 'dob', 'nationality_id']);

        $missingDetails = [];
        foreach ($requiredProperty as $value) {
            if (empty($cyberQuoteRequest[$value])) {
                $propertyName = match ($value) {
                    'dob' => 'date of birth',
                    'nationality_id' => 'nationality',
                    default => str_replace(['-', '_'], ' ', $value)
                };
                array_push($missingDetails, ucwords($propertyName));
            }
        }

        // Check for ID number (either passport or emirates ID)
        if (empty($cyberQuoteRequest['id_number'])) {
            array_push($missingDetails, 'ID Number (Passport or Emirates ID)');
        }

        $missingDetailCount = count($missingDetails);
        if ($missingDetailCount) {
            $message = 'Missing Info: '.implode(', ', $missingDetails);
        }

        return ['status' => $missingDetailCount ? false : true, 'message' => $message];
    }
}
