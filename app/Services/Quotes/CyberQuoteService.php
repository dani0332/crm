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
use App\Facades\Ken;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BranchAssignmentService;
use App\Services\CustomerInsuredService;
use App\Services\LookupService;
use App\Services\SplitPaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CyberQuoteService extends BaseQuoteService
{
    public function __construct(
        private CustomerInsuredService $customerInsuredService
    ) {
        parent::__construct(QuoteTypes::CYBER);
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
            'nationality',
            'insuranceProviderPlan',
            'cyberQuote',
            'cyberQuote.coverage',
            'branch:id,name',
            'leadGenerator:id,name',
        ])
            ->filter(forTotalLeadsCount: $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount)
            ->when(request()->filled('created_at_start') || request()->filled('created_at_end'), function ($q) {
                $q->filterByCreatedAt(request('created_at_start'), request('created_at_end'));
            })
            ->filterByDate('policy_expiry_date', 'previous_policy_expiry_date')
            ->filterByDate('policy_expiry_date_end', 'previous_policy_expiry_date', false)
            ->filterByPaymentDueDates('payment_due_date')
            ->filterByDateRange('booking_date', 'policy_booking_date')
            ->filterBy('payment_status_id')
            ->filterBy('is_ecommerce', isBool: true)
            ->filterIn('insurer_aml_status')
            ->filterIn('plan_name', 'plan_id')
            ->filterByDateRange('transaction_approved_dates', 'transaction_approved_at')
            ->filterBy('source')
            ->filterBy('ea_model')
            ->filterByLeadGeneratorName(request('lead_generator'))
            ->when(request()->filled('api_issuance_status_id'), function ($q) {
                $values = is_array(request('api_issuance_status_id'))
                    ? request('api_issuance_status_id')
                    : [request('api_issuance_status_id')];

                $hasBlank = in_array('blank', $values);
                $numericValues = array_filter($values, fn ($v) => $v !== 'blank' && is_numeric($v));

                $q->where(fn ($subQuery) => $subQuery
                    ->when(! empty($numericValues), fn ($q) => $q->whereIn('api_issuance_status_id', $numericValues))
                    ->when($hasBlank, fn ($q) => $q->orWhereNull('api_issuance_status_id'))
                );
            })
            ->filterIn('insurer_api_status_id')
            ->when(request()->filled('sic_advisor_requested') && request('sic_advisor_requested') !== 'All', function ($q) {
                $q->whereHas('cyberQuote', function ($subQuery) {
                    $subQuery->where('sic_advisor_requested', request('sic_advisor_requested'));
                });
            })
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

        if ($getQuery) {
            return $query;
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
            ->select(['personal_quotes.*'])
            ->with('cyberQuote')
            ->when($allDetails, function ($q) {
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
                ]);
            })
            ->where('uuid', $uuid)->firstOrFail();

        // Set customer_type - Cyber quotes always have Individual customer type
        $quote->customer_type = CustomerTypeEnum::Individual;

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
        if (app()->environment('production')) {
            $automationUserEmail = PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL;
        } else {
            // Non-production environments use test/UAT email from app storage
            $automationUserEmail = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_HAPPINESS_SUPPORT_USER_EMAIL, useCache: true);
        }

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
        $quotePlans = $this->getQuotePlans($id);

        // getQuotePlans() returns string for errors, array for success
        if (is_string($quotePlans)) {
            return $quotePlans;
        }

        // Success response: array with 'quotes' key containing 'plans'
        return $quotePlans['quotes']['plans'] ?? [];
    }

    public function getQuotePlans($id, bool $getLatestRating = false)
    {
        $plansDataArr = [
            'quoteUID' => $id,
            'lang' => 'en',
            'getLatestRating' => $getLatestRating,
            'callSource' => strtolower(LeadSourceEnum::IMCRM),
        ];

        try {
            $response = Ken::request('/cyber/get-quote-plans', 'post', $plansDataArr);

            if (isset($response['message'])) {
                return $response['message'];
            }

            if (isset($response['msg'])) {
                return $response['msg'];
            }

            if (isset($response['error'])) {
                return $response['error'];
            }

            return $response;
        } catch (ConnectionException $e) {
            return 'Connection error occurred.';
        } catch (\Exception $e) {
            return 'An unexpected error occurred.';
        }
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
                'escalationLink' => '',
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
            // OE user not updated by BA yet, production approval team will be the default recipient
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

        $escalationLink = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ESCALATION_LINK, '');

        return [
            'cc' => $cc,
            'recipientEmail' => $recipientEmail,
            'recipientName' => $recipientName,
            'escalationLink' => $escalationLink,
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
            return [getAppStorageValueByKey(ApplicationStorageEnums::CYBER_CAPTURE_FAILURE_EMAIL), 'Production Approval Team'];
        }

        return [getAppStorageValueByKey(ApplicationStorageEnums::CYBER_CAPTURE_FAILURE_EMAIL, useCache: true), 'Production Approval Team'];
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

        return DB::table('personal_quotes as pq')
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
