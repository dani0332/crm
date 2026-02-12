<?php

namespace App\Services\Quotes;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\DeviceMake;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
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
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
            'nationality',
            'insuranceProviderPlan',
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

        if (request()->has('debug') && request()->debug == true) {
            echo $query->toRawSql();
            exit;
        }

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function getOne(string $uuid, $allDetails = false)
    {
        return $this->baseQuery()
            ->with('deviceQuote')
            ->when($allDetails, function ($q) {
                $entityCustomerType = CustomerTypeEnum::Entity;
                $individualCustomerType = CustomerTypeEnum::Individual;

                $q->with([
                    'quoteStatus',
                    'advisor',
                    'paymentStatus',
                    'quoteDetail',
                    'quoteDetail.lostReason',
                    'renewalBatchModel',
                    'nationality',
                    'customer',
                    'customer.additionalContactInfo',
                    'insuranceProvider:id,text,code',
                    'insuranceProviderPlan.insuranceProvider',
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

    public function getShowData(string $uuid)
    {
        $quote = $this->getOne($uuid, true);
        $data = $this->getShowCommonData($quote);

        $data['permissions']['canEditQuote'] = ($this->can(Auth::user(), PermissionsEnum::DEVICE_QUOTES_EDIT) || (userHasProduct(quoteTypeCode::Device) && $this->can(Auth::user(), PermissionsEnum::VIEW_ALL_LEADS)));

        return [
            'canAddBatchNumber' => $this->hasRole(Auth::user(), RolesEnum::DeviceManager),
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
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
            'phoneMakeId' => $data['make_id'],
            'phoneModelId' => $data['model_id'],
            'imei' => $data['imei'],
            'quoteTypeId' => (int) $this->quoteType->id(),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'whatsappConsent' => false,
            'advisorId' => (! $this->hasRole(Auth::user(), RolesEnum::Admin)) ? Auth::id() : null,
        ];

        // Make API request to save the savings quote
        $response = Capi::request('/api/device/create', 'post', $data);

        if (isset($response->uuid)) {
            $this->selfAssign(QuoteTypes::DEVICE, $response->uuid, true);
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
    public function determineDeviceNgiRecipient($quote, string $processInvolved, $cc): ?array
    {
        $isBookingFailure = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;

        if ($isBookingFailure) {
            return $this->getProductionApprovalTeamRecipient($processInvolved, $cc);
        }

        if ($quote?->advisor) {
            return ['recipientEmail' => $quote->advisor->email, 'recipientName' => $quote->advisor->name, 'processInvolved' => $processInvolved, 'cc' => $cc];
        }

        return $this->getDeviceNgiFallbackRecipient($processInvolved, $cc);
    }

    /**
     * Get Production Approval Team recipient for Device/NGI booking failures.
     *
     * @return array{email: string, name: string}
     */
    private function getProductionApprovalTeamRecipient($processInvolved, $cc): array
    {
        $toEmail = $this->getDeviceFailureEmailTo();

        LoggerService::info('AutomationFailedService - Device/NGI Booking failure, sending to Production Approval Team', extra: [
            'recipientEmail' => $toEmail,
        ]);

        return ['recipientEmail' => $toEmail, 'recipientName' => 'Production Approval Team', 'processInvolved' => $processInvolved, 'cc' => $cc];
    }

    /**
     * Get fallback recipient for Device/NGI when no advisor is assigned.
     *
     * @return array{email: string, name: string}
     */
    private function getDeviceNgiFallbackRecipient($processInvolved, $cc): array
    {
        $fallbackEmail = $this->getDeviceFailureEmailTo();

        LoggerService::info('AutomationFailedService - Device/NGI no advisor assigned, sending to fallback', extra: [
            'fallbackEmail' => $fallbackEmail,
        ]);

        return ['recipientEmail' => $fallbackEmail, 'recipientName' => 'Device Support Team', 'processInvolved' => $processInvolved, 'cc' => $cc];
    }

    /**
     * Get Device failure email from ApplicationStorage with fallback.
     */
    private function getDeviceFailureEmailTo(): string
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
            ?: 'production.approval.team@insurancemarket.ae';
    }

    private function getDeviceDistributionEmails(): array
    {
        $configured = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL, useCache: true);

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
            return [getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_CAPTURE_FAILURE_EMAIL), 'Production Approval Team'];
        }

        return [getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_CAPTURE_FAILURE_EMAIL, useCache: true), 'Production Approval Team'];
    }

    /**
     * Assemble Device/NGI CC payload with normalized email list.
     *
     * @return array{approvalemail: string|null, prodemail: string|null, advisoremail: string|null, ccEmails: array}
     */
    public function buildDeviceCcPayload($quote, string $processInvolved, $cc = []): array
    {
        $ccEmails = $this->parseCommaSeparatedEmails(
            getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC)
        );
        if (! empty($cc['approvalemail'])) {
            $ccEmails[] = $cc['approvalemail'];
        }
        $payload = [
            'approvalemail' => ! empty($ccEmails) ? implode(',', $ccEmails) : null,
            'prodemail' => getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL),
            'advisoremail' => null,
            'ccEmails' => $ccEmails,
        ];

        if ($processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY && $quote?->advisor?->email) {
            $payload['advisoremail'] = $quote->advisor->email;
        }

        return $payload;
    }

    private function parseCommaSeparatedEmails(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $rawEmails = preg_split('/[,\s]+/', trim($value));

        return array_values(array_filter($rawEmails, static fn ($email) => ! empty($email)));
    }

}
