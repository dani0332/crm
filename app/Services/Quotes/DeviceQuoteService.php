<?php

namespace App\Services\Quotes;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\NgiEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Jobs\PolicyIssuanceJob;
use App\Models\DeviceMake;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\BranchAssignmentService;
use App\Services\EACollaborateHelper;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsJob;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeviceQuoteService extends BaseQuoteService
{
    public function __construct()
    {
        parent::__construct(QuoteTypes::DEVICE);
    }

    public function getData(bool $paginted = false, bool $forExport = false, bool $getTotalCount = false)
    {
        $query = $this->baseQuery()->with([
            'quoteStatus',
            'advisor',
            'advisor.primaryBranch',
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
            'nationality',
            'insuranceProviderPlan',
            'branch:id,name',
            'leadGenerator:id,name',
            'expertAdvisor:id,name',
        ])
            ->filter(forTotalLeadsCount: $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount)
            ->when(empty(request('booking_date')), function ($query) {
                $query->filterByCreatedAt(request('created_at_start'), request('created_at_end'));
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
            ->filterByAdvisorAssignedDates('quoteDetail', ['advisor_assigned_date_start', 'advisor_assigned_date_end'], verifyQuoteStatus: true)
            ->filterIn('renewal_batch_id')
            ->filterBy('assignment_type', ignoreAll: true)
            ->filterByPrivateClient(request('private_client'))
            ->when(request()->filled('insurer_tax_invoice_number'), function ($q) {
                $q->whereHas('payments', function ($subQuery) {
                    $subQuery->where('insurer_tax_number', request('insurer_tax_invoice_number'));
                });
            })
            ->when(request()->filled('insurer_commission_tax_invoice_number'), function ($q) {
                $q->whereHas('payments', function ($subQuery) {
                    $subQuery->where('insurer_commmission_invoice_number', request('insurer_commission_tax_invoice_number'));
                });
            })
            ->when(request()->filled('payment_authorised_date'), function ($q) {
                $authorizedAtRange = request('payment_authorised_date');
                if (is_array($authorizedAtRange) && count($authorizedAtRange) >= 2) {
                    $startDate = $authorizedAtRange[0];
                    $endDate = $authorizedAtRange[1];
                    if ($startDate && $endDate) {
                        $q->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereBetween('authorized_at', [
                                Carbon::parse($startDate)->startOfDay(),
                                Carbon::parse($endDate)->endOfDay(),
                            ]);
                        });
                    }
                }
            })
            ->when(request()->filled('payment_capture_date'), function ($q) {
                $capturedAtRange = request('payment_capture_date');
                if (is_array($capturedAtRange) && count($capturedAtRange) >= 2) {
                    $startDate = $capturedAtRange[0];
                    $endDate = $capturedAtRange[1];
                    if ($startDate && $endDate) {
                        $q->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereBetween('captured_at', [
                                Carbon::parse($startDate)->startOfDay(),
                                Carbon::parse($endDate)->endOfDay(),
                            ]);
                        });
                    }
                }
            });

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        if (request()->has('debug') && request()->debug == true) {
            echo $query->toRawSql();
            exit;
        }

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function postProcessDeviceQuotes($quotes)
    {
        $quotes->getCollection()->transform(function ($item) {
            $item->branch_name = ! $item->is_branch_applicable
                ? 'N/A'
                : ($item?->branch?->name ?? app(BranchAssignmentService::class)
                    ->getBranchName($item?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Device));

            return $item;
        });

        return $quotes;
    }

    public function getOne(string $uuid, $allDetails = false)
    {
        $quote = $this->baseQuery()
            ->with('deviceQuote')
            ->when($allDetails, function ($q) {
                $entityCustomerType = CustomerTypeEnum::Entity;
                $individualCustomerType = CustomerTypeEnum::Individual;
                $q->leftJoin('lookups as lu', 'lu.id', '=', 'personal_quotes.transaction_type_id')
                    ->with([
                        'quoteStatus',
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
                        'insuranceProviderPlan.insuranceProvider',
                        'latestInsured',
                        'latestInsured.insuredKyc' => function ($query) {
                            $query->select([
                                'id',
                                'insured_id',        // advisor primary key
                                'id_expiry_date',
                            ]);
                        },
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
                        'leadGenerator',
                        'expertAdvisor',
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
            ")->addSelect('lu.text as transaction_type_text');
            })
            ->where('uuid', $uuid)->firstOrFail();

        $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Device));

        return $quote;
    }

    public function getShowData(string $uuid)
    {
        $quote = $this->getOne($uuid, true);
        $data = $this->getShowCommonData($quote);

        $data['permissions']['canEditQuote'] = ($this->can(Auth::user(), PermissionsEnum::DEVICE_QUOTES_EDIT) || (userHasProduct(quoteTypeCode::Device) && $this->can(Auth::user(), PermissionsEnum::VIEW_ALL_LEADS)));
        $automationUserEmail = getAppStorageValueByKey(ApplicationStorageEnums::SMART_PHONE_HAPPINESS_SUPPORT_USER_EMAIL, useCache: true); // Test/UAT email

        if ($quote->advisor && $quote->advisor->email === $automationUserEmail) {
            $quote->advisor->name = PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_LABEL;
        }
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        return [
            'canAddBatchNumber' => $this->hasAnyRole(Auth::user(), [RolesEnum::SmartPhoneManager]),
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
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
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/device/get-quote-plans';
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
                $getContents = (string) $kenRequest->getBody();
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
            } else {
                $responseBodyAsString = $contents;
            }

            return $responseBodyAsString;
        } catch (ConnectException $e) {
            $responseBodyAsString = 'Connection error occurred.';

            return $responseBodyAsString;
        } catch (RequestException $e) {
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
        $lookUpData = app(LookupService::class)->getDeviceCoverages();
        $deviceMakes = DeviceMake::with('deviceModels')->get();

        return [
            'lookUpData' => $lookUpData,
            'deviceMakes' => $deviceMakes,
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
            'purchaseMonth' => $data['month_of_purchase'],
            'purchaseYear' => $data['year_of_purchase'],
            'makeId' => $data['make_id'],
            'modelId' => $data['model_id'],
            'imei' => $data['imei'],
            'quoteTypeId' => (int) $this->quoteType->id(),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'whatsappConsent' => false,
            'advisorId' => (! $this->hasRole(Auth::user(), RolesEnum::Admin)) ? Auth::id() : null,
        ];

        EACollaborateHelper::applyEAIMCRMSource($data);

        if (request()->input('ea_model')) {
            LoggerService::info('DeviceQuoteService: CAPI payload for EA lead', [
                'ea_model' => $data['eaModel'] ?? request()->input('ea_model'),
                'source' => $data['source'] ?? null,
                'email' => $data['email'] ?? null,
                'lead_generator_id' => $data['leadGeneratorId'] ?? null,
                'advisor_id' => $data['advisorId'] ?? null,
                'quote_type_id' => $data['quoteTypeId'] ?? null,
            ]);
        }

        // Make API request to save the device quote
        $response = Capi::request('/api/v1/device/create', 'post', $data);

        if (request()->input('ea_model')) {
            LoggerService::info('DeviceQuoteService: CAPI response for EA lead', [
                'ea_model' => request()->input('ea_model'),
                'quote_uid' => $response->uuid ?? $response->quoteUID ?? null,
                'message' => $response->message ?? null,
                'has_errors' => ! empty($response->errors),
            ]);

            $deviceQuoteUid = $response->uuid ?? $response->quoteUID ?? null;
            if ($deviceQuoteUid) {
                $eaQuote = PersonalQuote::where('uuid', $deviceQuoteUid)->first();
                if ($eaQuote) {
                    EACollaborateHelper::dispatchLeadSubmittedEmail($eaQuote, 'device');
                }
            }
        }

        if (isset($response->code) && ! in_array($response->code, [200, 201], true) || isset($response->status) && ! in_array($response->status, [200, 201], true)) {
            return $response;
        }
        if (isset($response->uuid) && $response->uuid != '') {
            LoggerService::info(self::class.' - create: Assigning quote to self', extra: [
                'quote_uuid' => $response->uuid,
            ]);
            $this->selfAssign(QuoteTypes::DEVICE, $response->uuid, true);
            $lead = $this->baseQuery()->where('uuid', $response->uuid)->first();
            if ($lead) {
                $lead->advisor_id = Auth::id();
                $lead->save();
            }
        }

        return $response;
    }

    public function update(string $uuid, array $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->baseQuery()->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no',
            ]);

            $quoteData['updated_by_id'] = Auth::id();
            $quote->update($quoteData);

            if (! empty($quote->deviceQuote)) {
                // Combine month and year into purchase_date in Y-m format
                $purchaseDate = sprintf('%04d-%02d', $data['year_of_purchase'], $data['month_of_purchase']);
                $quote->deviceQuote->purchase_date = $purchaseDate.'-01';
                $quote->deviceQuote->model_id = $data['model_id'];
                $quote->deviceQuote->make_id = $data['make_id'];
                $quote->deviceQuote->imei = $data['imei'];
                $quote->deviceQuote->save();
            }

            return $quote;
        });
    }

    public function getDeviceCoverages()
    {
        return app(LookupService::class)->getDeviceCoverages();
    }

    /**
     * Determine recipient for Device/NGI quotes.
     * FRD: Booking Details API failure → Production Approval Team
     * Other failures → Assigned SIC Advisor (or fallback to PA Team)
     *
     * @return array{email: string, name: string}|null
     */
    public function determineDeviceNgiRecipient($quote, array $cc, string $processInvolved,
        ?string $recipientEmail,
        ?string $recipientName): ?array
    {
        $isBookingFailure = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;
        $payload = [];
        if ($isBookingFailure) {
            $payload = $this->getProductionApprovalTeamRecipient();
        } elseif ($quote?->advisor) {
            $payload = ['recipientEmail' => $quote?->advisor?->email, 'recipientName' => $quote?->advisor?->name];
        } else {
            $payload = $this->getDeviceNgiFallbackRecipient();
        }
        $distribution = $this->parseCommaSeparatedEmails(
            getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC)
        );

        // CC advisor when they are not the resolved To address (e.g. booking failure sends To production, not the job's default recipient).
        if ($quote?->advisor?->email && $quote->advisor->email !== $payload['recipientEmail']) {
            $distribution[] = $quote->advisor->email;
        }

        $distribution = array_values(array_unique(array_filter($distribution)));
        if (! empty($distribution)) {
            $cc = $distribution;
        }

        return array_merge(['cc' => $cc, 'processInvolved' => $processInvolved, 'recipientEmail' => $recipientEmail, 'recipientName' => $recipientName], $payload);
    }

    /**
     * Get Production Approval Team recipient for Device/NGI booking failures.
     *
     * @return array{email: string, name: string}
     */
    private function getProductionApprovalTeamRecipient(): array
    {
        $toEmail = $this->getDeviceFailureEmailTo();

        LoggerService::info('Device/NGI Booking failure, sending to Production Approval Team', extra: [
            'recipientEmail' => $toEmail,
        ]);

        return ['recipientEmail' => $toEmail, 'recipientName' => 'Production Approval Team'];
    }

    /**
     * Get fallback recipient for Device/NGI when no advisor is assigned.
     *
     * @return array{email: string, name: string}
     */
    private function getDeviceNgiFallbackRecipient(): array
    {
        $fallbackEmail = $this->getDeviceFailureEmailTo();

        LoggerService::info('Device/NGI no advisor assigned, sending to fallback', extra: [
            'fallbackEmail' => $fallbackEmail,
        ]);

        return ['recipientEmail' => $fallbackEmail, 'recipientName' => 'Device Support Team'];
    }

    /**
     * Get Device failure email from ApplicationStorage with fallback.
     */
    private function getDeviceFailureEmailTo(): string
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
            ?: 'production.approval.team@insurancemarket.ae';
    }

    private function parseCommaSeparatedEmails(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $rawEmails = preg_split('/[,\s]+/', trim($value));

        return array_values(array_filter($rawEmails, static fn ($email) => ! empty($email)));
    }

    /**
     * IMCRM manual recovery: eligible when create-policy succeeded but GetAndUploadPolicyDocs failed at least three times.
     */
    public function isEligibleForReTriggerGetAndUploadPolicyDocuments(PolicyIssuance $policyIssuance): bool
    {
        if ($policyIssuance->completed_step !== NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE) {
            return false;
        }

        if ($policyIssuance->status !== PolicyIssuanceEnum::FAILED_STATUS) {
            return false;
        }

        $failedDocAttempts = $policyIssuance->policyIssuanceLogs()
            ->where('step', NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM)
            ->where('status', PolicyIssuanceEnum::FAILED_STATUS)
            ->count();

        return $failedDocAttempts >= 3;
    }

    /**
     * Mark issuance pending for queue visibility, then dispatch immediate NGI document fetch/upload job (no initial delay).
     * Status is persisted before dispatch so sync drivers cannot overwrite the job's final status.
     *
     * @throws \InvalidArgumentException When eligibility checks fail
     */
    public function reTriggerGetAndUploadPolicyDocumentsAfterRepeatedFailures(PolicyIssuance $policyIssuance): void
    {
        if (! $this->isEligibleForReTriggerGetAndUploadPolicyDocuments($policyIssuance)) { // only retry doc step if all retries failed.
            throw new \InvalidArgumentException('Policy issuance is not eligible for document sync re-trigger.');
        }

        $policyIssuance->update([
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
        ]);

        NgiGetPolicyDocumentsJob::dispatch($policyIssuance->id);

        LoggerService::info('Device NGI: manual re-trigger dispatched GetAndUploadPolicyDocs job without initial delay', extra: [
            'policy_issuance_id' => $policyIssuance->id,
        ]);
    }

    public function identifyAutomationStepToReTrigger(PolicyIssuance $policyIssuance): void
    {
        $isDownloadDocStepFailed = $policyIssuance->completed_step == NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE && $policyIssuance->status === PolicyIssuanceEnum::FAILED_STATUS;
        if ($isDownloadDocStepFailed) { // only doc step required special treatment.
            $this->reTriggerGetAndUploadPolicyDocumentsAfterRepeatedFailures($policyIssuance);
        } else {
            // for other steps, just mark as pending and dispatch job.
            $policyIssuance->update([
                'status' => PolicyIssuanceEnum::PENDING_STATUS,
            ]);
            PolicyIssuanceJob::dispatch($policyIssuance->id);
        }
    }

}
