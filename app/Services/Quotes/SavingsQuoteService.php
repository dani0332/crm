<?php

namespace App\Services\Quotes;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\SavingsQuote;
use App\Services\LookupService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavingsQuoteService extends BaseQuoteService
{
    public function __construct()
    {
        parent::__construct(QuoteTypes::SAVINGS);
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
            'nationality',
        ])
            ->filter(forTotalLeadsCount: $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount)
            ->filterByCreatedAt(request('created_at_start'), request('created_at_end'))
            ->when(request('investment_frequency'), function ($q) {
                $q->whereHas('savingsQuote', function ($sq) {
                    $sq->where('investment_frequency', request('investment_frequency'));
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

    // TODO: Remove this function after the API is ready
    private function tempMockApi($data)
    {
        $getUUID = function (): string {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $length = 8;

            do {
                $uid = '';
                for ($i = 0; $i < $length; $i++) {
                    $uid .= $characters[random_int(0, strlen($characters) - 1)];
                }
            } while (PersonalQuote::where('uuid', $uid)->exists());

            return $uid;
        };

        $uuid = $getUUID();

        $customer = Customer::firstOrCreate([
            'email' => $data['email'],
        ], [
            'uuid' => Str::uuid(),
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'mobile_no' => $data['mobileNo'],
            'dob' => $data['dob'],
            'nationality_id' => $data['nationalityId'],
        ]);

        $personalQuote = PersonalQuote::create([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'dob' => $data['dob'],
            'nationality_id' => $data['nationalityId'],
            'gender' => $data['gender'],
            'quote_type_id' => $data['quoteTypeId'],
            'uuid' => $uuid,
            'code' => "{$this->quoteType->shortCode()}{$uuid}",
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'customer_id' => $customer->id,
        ]);

        PersonalQuoteDetail::create([
            'personal_quote_id' => $personalQuote->id,
        ]);

        // Get lookup data to map IDs back to codes for database storage
        $lookupData = $this->getSavingsQuoteLookUpData();

        // Convert lookupData items to collections
        $savingsTenure = collect($lookupData['savingsTenure']);
        $savingsPurpose = collect($lookupData['savingsPurpose']);
        $savingsInvestmentType = collect($lookupData['savingsInvestmentType']);

        $tenureOfSavings = $savingsTenure->where('id', $data['tenureId'])->first()->code ?? null;
        $purposeOfSavings = $savingsPurpose->where('id', $data['purposeId'])->first()->code ?? null;
        $investmentFrequency = $savingsInvestmentType->where('id', $data['investmentCriteriaId'])->first()->code ?? null;

        SavingsQuote::create([
            'personal_quote_id' => $personalQuote->id,
            'marital_status_id' => $data['maritalStatusId'],
            'tenure_of_savings' => $tenureOfSavings,
            'has_nicotine' => $data['nicotineStatus'],
            'purpose_of_savings' => $purposeOfSavings,
            'currency_id' => $data['currencyId'],
            'amount' => $data['investmentAmount'],
            'investment_frequency' => $investmentFrequency,
            'additional_notes' => $data['additionalNotes'],
        ]);

        return (object) [
            'quoteUID' => $uuid,
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
            'tenureId' => (int) $data['tenure_of_savings'],
            'nicotineStatus' => (int) $data['has_nicotine'],
            'purposeId' => (int) $data['purpose_of_savings'],
            'currencyId' => (int) $data['currency_id'],
            'investmentAmount' => (float) $data['amount'],
            'investmentCriteriaId' => (int) $data['investment_frequency'],
            'additionalNotes' => $data['additional_notes'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'advisorId' => (! Auth::user()->hasRole(RolesEnum::Admin)) ? Auth::id() : null,
        ];

        // Make API request to save the savings quote
        $response = Capi::request('/api/v1-save-savings-quote', 'post', $data);

        return $response;
    }

    public function getOne(string $uuid, $allDetails = false)
    {
        return $this->baseQuery()->with([
            'savingsQuote',
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
                    'payments',
                    'quoteDetail',
                    'quoteDetail.lostReason',
                    'renewalBatchModel',
                    'nationality',
                    'customer',
                    'customer.additionalContactInfo',
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

            $quoteData['updated_by_id'] = Auth::user()->id;
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
            'canAddBatchNumber' => Auth::user()->hasRole(RolesEnum::SavingsManager),
            ...$data,
        ];
    }

    public function getSavingsQuoteLookUpData()
    {
        return app(LookupService::class)->getSavingsQuoteLookUpData();
    }

    public function getAvailablePlans($uuid)
    {
        $result = [];
        $plans = $this->listQuotePlans($uuid);

        // dd($plans);
        // $collection = collect($plans);

        // $seniorPlans = $collection->where('isSeniorPlan', true);
        // $normalPlans = $collection->where('isSeniorPlan', false);

        // $result['normalPlans'] = array_values($normalPlans->toArray());
        // $result['seniorPlans'] = array_values($seniorPlans->toArray());

        return $result;
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
}
