<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Enums\CustomerTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Services\LookupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CyberQuoteService extends BaseQuoteService
{
    public function __construct()
    {
        parent::__construct(QuoteTypes::CYBER);
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

        if (request()->has('debug') && request()->debug == 'true') {
            echo $query->toRawSql();
            exit;
        }

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function getOne(string $uuid, $allDetails = false)
    {
        return $this->baseQuery()
            ->when($allDetails, function ($q) {
                $entityCustomerType = CustomerTypeEnum::Entity;
                $individualCustomerType = CustomerTypeEnum::Individual;

                $q->with([
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

    public function getShowData(string $uuid)
    {
        $quote = $this->getOne($uuid, true);
        $data = $this->getShowCommonData($quote);

        $data['permissions']['canEditQuote'] = ($this->can(Auth::user(), PermissionsEnum::CYBER_QUOTES_EDIT) || (userHasProduct(quoteTypeCode::CYBER) && $this->can(Auth::user(), PermissionsEnum::VIEW_ALL_LEADS)));

        return [
            'canAddBatchNumber' => $this->hasRole(Auth::user(), RolesEnum::CyberManager),
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
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id', 'emirate_of_registration_id',
            ]);

            $quoteData['updated_by_id'] = Auth::id();

            $quote->update($quoteData);

            return $quote;
        });
    }

    public function getCyberCoverages()
    {
        return app(LookupService::class)->getCyberCoverages();
    }
}

