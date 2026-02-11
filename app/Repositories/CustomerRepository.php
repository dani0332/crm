<?php

namespace App\Repositories;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Http\Requests\CustomerUploadRequest;
use App\Imports\CustomersImport;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\Insured;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomerRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Customer::class;
    }

    public function fetchGetData()
    {
        $filterValue = request()->get('search_value');
        $filterType = request()->get('search_type');
        $filterColumns = ['email', 'first_name', 'entity_name', 'insured_first_name', 'mobile_no', 'uuid'];

        if (! $this->isValidFilter($filterType, $filterValue, $filterColumns)) {
            return [];
        }

        $filterData = $this->processFilterData($filterType, $filterValue);

        if ($filterData === null) {
            return [];
        }

        return $this->buildUnionQuery($filterType, $filterData);
    }

    private function isValidFilter($filterType, $filterValue, array $filterColumns): bool
    {
        return in_array($filterType, $filterColumns) && ! empty($filterType) && ! empty($filterValue);
    }

    private function processFilterData(string $filterType, string $filterValue): ?array
    {
        if ($filterType === 'entity_name') {
            return $this->processEntityFilter($filterValue);
        }

        return $this->processCustomerFilter($filterType, $filterValue);
    }

    private function processEntityFilter(string $filterValue): ?array
    {
        $entitiesIds = Entity::where('company_name', $filterValue)->pluck('id');

        if ($entitiesIds->isEmpty()) {
            return null;
        }

        return [
            'entitiesIds' => $entitiesIds,
            'customerIds' => collect([]),
            'quoteIds' => $this->initializeQuoteIdsArray(),
        ];
    }

    private function processCustomerFilter(string $filterType, string $filterValue): ?array
    {
        $quoteIds = $this->initializeQuoteIdsArray();

        if ($filterType === 'insured_first_name') {
            $result = $this->processInsuredFilter($filterValue, $quoteIds);
            if ($result === null) {
                return null;
            }
            [$customerIds, $quoteIds] = $result;
        } else {
            $customerIds = Customer::where($filterType, $filterValue)->pluck('id');
        }

        if (empty($customerIds) || $customerIds->isEmpty()) {
            return null;
        }

        return [
            'entitiesIds' => collect([]),
            'customerIds' => $customerIds,
            'quoteIds' => $quoteIds,
        ];
    }

    private function processInsuredFilter(string $filterValue, array &$quoteIds): ?array
    {
        $insuredIds = Insured::where('first_name', $filterValue)->pluck('id');

        if ($insuredIds->isEmpty()) {
            return null;
        }

        $customerInsuredRecords = CustomerInsured::whereIn('insured_id', $insuredIds)
            ->where('is_active', true)
            ->get(['customer_id', 'quote_type_id', 'quote_request_id']);

        if ($customerInsuredRecords->isEmpty()) {
            return null;
        }

        $this->mapQuoteIdsByType($customerInsuredRecords, $quoteIds);

        $customerIds = $customerInsuredRecords->pluck('customer_id')->unique()->values();

        return [$customerIds, $quoteIds];
    }

    private function mapQuoteIdsByType($customerInsuredRecords, array &$quoteIds): void
    {
        foreach ($customerInsuredRecords as $record) {
            switch ($record->quote_type_id) {
                case QuoteTypeId::Car:
                    $quoteIds['car'][] = $record->quote_request_id;
                    break;
                case QuoteTypeId::Home:
                    $quoteIds['home'][] = $record->quote_request_id;
                    break;
                case QuoteTypeId::Health:
                    $quoteIds['health'][] = $record->quote_request_id;
                    break;
                case QuoteTypeId::Life:
                    $quoteIds['life'][] = $record->quote_request_id;
                    break;
                case QuoteTypeId::Business:
                    $quoteIds['business'][] = $record->quote_request_id;
                    break;
                case QuoteTypeId::Travel:
                    $quoteIds['travel'][] = $record->quote_request_id;
                    break;
                case QuoteTypeId::Bike:
                case QuoteTypeId::Yacht:
                case QuoteTypeId::Pet:
                case QuoteTypeId::Cycle:
                case QuoteTypeId::Jetski:
                    $quoteIds['personal'][] = $record->quote_request_id;
                    break;
                default:
                    break;
            }
        }
    }

    private function initializeQuoteIdsArray(): array
    {
        return [
            'car' => [],
            'home' => [],
            'health' => [],
            'life' => [],
            'business' => [],
            'travel' => [],
            'personal' => [],
        ];
    }

    private function buildUnionQuery(string $filterType, array $filterData)
    {
        $carQuotes = $this->buildCarQuotesQuery($filterType, $filterData);
        $homeQuotes = $this->buildHomeQuotesQuery($filterType, $filterData);
        $healthQuotes = $this->buildHealthQuotesQuery($filterType, $filterData);
        $lifeQuotes = $this->buildLifeQuotesQuery($filterType, $filterData);
        $businessQuotes = $this->buildBusinessQuotesQuery($filterType, $filterData);
        $travelQuotes = $this->buildTravelQuotesQuery($filterType, $filterData);
        $personalQuotes = $this->buildPersonalQuotesQuery($filterType, $filterData);

        return $personalQuotes
            ->union($healthQuotes)
            ->union($lifeQuotes)
            ->union($travelQuotes)
            ->union($homeQuotes)
            ->union($businessQuotes)
            ->union($carQuotes)
            ->simplePaginate()
            ->withQueryString();
    }

    private function buildCarQuotesQuery(string $filterType, array $filterData)
    {
        return $this->buildQuoteQuery(
            CarQuote::class,
            QuoteTypeId::Car,
            $filterType,
            $filterData,
            'car',
            false
        );
    }

    private function buildHomeQuotesQuery(string $filterType, array $filterData)
    {
        return $this->buildQuoteQuery(
            HomeQuote::class,
            QuoteTypeId::Home,
            $filterType,
            $filterData,
            'home',
            false
        );
    }

    private function buildHealthQuotesQuery(string $filterType, array $filterData)
    {
        return $this->buildQuoteQuery(
            HealthQuote::class,
            QuoteTypeId::Health,
            $filterType,
            $filterData,
            'health',
            false
        );
    }

    private function buildLifeQuotesQuery(string $filterType, array $filterData)
    {
        return $this->buildQuoteQuery(
            LifeQuote::class,
            QuoteTypeId::Life,
            $filterType,
            $filterData,
            'life',
            false
        );
    }

    private function buildBusinessQuotesQuery(string $filterType, array $filterData)
    {
        return $this->buildQuoteQuery(
            BusinessQuote::class,
            QuoteTypeId::Business,
            $filterType,
            $filterData,
            'business',
            true
        );
    }

    private function buildTravelQuotesQuery(string $filterType, array $filterData)
    {
        return $this->buildQuoteQuery(
            TravelQuote::class,
            QuoteTypeId::Travel,
            $filterType,
            $filterData,
            'travel',
            false
        );
    }

    private function buildPersonalQuotesQuery(string $filterType, array $filterData)
    {
        $quoteIds = $filterData['quoteIds']['personal'] ?? [];
        $customerIds = $filterData['customerIds'];
        $entitiesIds = $filterData['entitiesIds'];

        return PersonalQuote::with(['advisor', 'customer', 'latestInsured' => function ($latestInsured) {
            $latestInsured->whereIn('customer_insured.quote_type_id', getPersonalQuoteTypeIds());
        }])
            ->when($filterType === 'insured_first_name' && ! empty($quoteIds), function ($query) use ($quoteIds) {
                $query->whereIn('id', $quoteIds);
            })
            ->when($filterType !== 'insured_first_name' && $customerIds->isNotEmpty(), function ($customer) use ($customerIds) {
                $customer->whereIn('customer_id', $customerIds);
            })
            ->when($entitiesIds->isNotEmpty(), function ($entity) use ($entitiesIds) {
                $entity->whereHas('quoteRequestEntityMapping', function ($mapping) use ($entitiesIds) {
                    $mapping->whereIn('entity_id', $entitiesIds);
                });
            })
            ->whereIn('quote_status_id', $this->getPolicyQuoteStatuses())
            ->select([
                'id', 'uuid', 'code', 'customer_id', 'policy_number', 'advisor_id',
                'policy_start_date', 'policy_expiry_date', 'quote_type_id',
                DB::raw("'' as business_type_of_insurance_id"),
            ])
            ->orderBy('created_at', 'desc');
    }

    private function buildQuoteQuery(
        string $modelClass,
        int $quoteTypeId,
        string $filterType,
        array $filterData,
        string $quoteKey,
        bool $includeBusinessType
    ) {
        $quoteIds = $filterData['quoteIds'][$quoteKey] ?? [];
        $customerIds = $filterData['customerIds'];
        $entitiesIds = $filterData['entitiesIds'];

        $selectFields = [
            'id', 'uuid', 'code', 'customer_id', 'policy_number', 'advisor_id',
            'policy_start_date', 'policy_expiry_date',
            DB::raw('"'.$quoteTypeId.'" as quote_type_id'),
        ];

        if ($includeBusinessType) {
            $selectFields[] = 'business_type_of_insurance_id';
        } else {
            $selectFields[] = DB::raw("'' as business_type_of_insurance_id");
        }

        return $modelClass::with(['advisor', 'customer', 'latestInsured'])
            ->when($filterType === 'insured_first_name' && ! empty($quoteIds), function ($query) use ($quoteIds) {
                $query->whereIn('id', $quoteIds);
            })
            ->when($filterType !== 'insured_first_name' && $customerIds->isNotEmpty(), function ($customer) use ($customerIds) {
                $customer->whereIn('customer_id', $customerIds);
            })
            ->when($entitiesIds->isNotEmpty(), function ($entity) use ($entitiesIds) {
                $entity->whereHas('quoteRequestEntityMapping', function ($mapping) use ($entitiesIds) {
                    $mapping->whereIn('entity_id', $entitiesIds);
                });
            })
            ->whereIn('quote_status_id', $this->getPolicyQuoteStatuses())
            ->select($selectFields)
            ->orderBy('created_at', 'desc');
    }

    private function getPolicyQuoteStatuses(): array
    {
        return [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::POLICY_BOOKING_FAILED,
            QuoteStatusEnum::POLICY_BOOKING_QUEUED,
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->with(['nationality'])->where($column, $value)->firstOrFail();
    }

    /**
     * @return bool
     */
    public function fetchStoreAdditionalContact($customerId, $data)
    {
        if ($data['key'] === GenericRequestEnum::EMAIL) {
            $isExistEmail = CustomerAdditionalContact::where('customer_id', $customerId)
                ->where('value', $data['value'])->where('key', 'email')->first();

            if ($isExistEmail) {
                return back()->with('success', 'Email Address already Exist. Please try another.');
            }

            $customer = $this->findOrFail($customerId);
            $customer->additionalContactInfo()->create($data);

            return $customer;
        } elseif ($data['key'] === GenericRequestEnum::MOBILE_NO) {
            $isExistMobile = CustomerAdditionalContact::where('customer_id', $customerId)
                ->where('value', $data['value'])->where('key', 'mobile_no')->first();

            if ($isExistMobile) {
                return back()->with('success', 'Mobile Number already Exist. Please try another.');
            }

            $customer = $this->findOrFail($customerId);
            $customer->additionalContactInfo()->create($data);

            return $customer;
        }

    }

    public function fetchGetAdditionalContacts($customerId, $quoteMobileNo)
    {
        $customer = $this->where('id', $customerId)->first();
        $additionalContacts = CustomerAdditionalContact::where('customer_id', $customerId)->orderBy('created_at', 'desc')->get();

        if (isset($customer) && $quoteMobileNo != $customer->mobile_no) {
            $customerMobileNo = (object) [
                'key' => 'mobile_no',
                'value' => isset($customer->mobile_no) ? $customer->mobile_no : '',
                'created_at' => isset($customer->created_at) ? $customer->created_at : '',
            ];
            $additionalContacts->push($customerMobileNo);
        }

        return $additionalContacts;
    }

    public function fetchCustomerUploadRecordsCreate(CustomerUploadRequest $customerUploadRequest, SendEmailCustomerService $sendEmailCustomerService, BerlinService $berlinService)
    {
        if ($customerUploadRequest->hasFile('file_name')) {
            return Excel::import(new CustomersImport(
                $customerUploadRequest->myalfred_expiry_date,
                $customerUploadRequest->cdb_id,
                $customerUploadRequest->inviatation_email,
                $sendEmailCustomerService,
                $berlinService
            ), $customerUploadRequest->file('file_name'));
        }

        vAbort('Something went wrong while uploading');
    }

    public function fetchReplicatePreviousAdditionalContacts($old_customer_id, $new_customer_id)
    {
        $customerPreviousContactInfo = CustomerAdditionalContact::where('customer_id', $old_customer_id)->get();
        foreach ($customerPreviousContactInfo as $customerPreInfo) {
            CustomerAdditionalContact::updateOrCreate([
                'customer_id' => $new_customer_id,
                'key' => $customerPreInfo->key,
                'value' => $customerPreInfo->value,
            ]);
        }
    }

    public function fetchUpdateCustomerDetails($customerId, $data)
    {
        info('fn:updateCustomerDetails - Updating customer details');

        $customer = Customer::with('nationality')->findOrFail($customerId);
        $customer->detail()->updateOrCreate(['customer_id' => $customerId], $data->only([
            'place_of_birth',
            'country_of_residence',
            'residential_address',
            'residential_status',
            'id_type',
            'id_issuance_date',
            'mode_of_contact',
            // 'transaction_value',
            'mode_of_delivery',
            'employment_sector',
            'customer_tenure',
        ]));

        $customer->refresh();

        info('fn:updateCustomerDetails - Updated customer details');

        return $customer;
    }

    public function fetchGetDataByContacts(array $request = [])
    {
        $customerIds = [];

        // Return empty collection if no email filters are provided
        if (empty($request['primary_email']) && empty($request['additional_email'])) {
            return collect([]);
        }

        // Check if primary_email filter is provided
        if (! empty($request['primary_email'])) {
            $primaryCustomers = Customer::where('email', $request['primary_email'])->pluck('id');
            $customerIds = array_merge($customerIds, $primaryCustomers->toArray());
        }
        // Check if additional_email filter is provided
        elseif (! empty($request['additional_email']) && empty($request['primary_email'])) {
            $additionalCustomers = CustomerAdditionalContact::where('key', 'email')
                ->where('value', $request['additional_email'])
                ->pluck('customer_id');
            $customerIds = array_merge($customerIds, $additionalCustomers->toArray());
        }

        // Return empty collection if no matching customers found for the provided email filters
        if (empty($customerIds)) {
            return collect([]);
        }

        // Remove duplicates from customer IDs
        $customerIds = array_unique($customerIds);

        // Query personal quotes for the found customer IDs
        return PersonalQuote::with(['advisor', 'customer', 'quoteStatus'])
            ->whereIn('customer_id', $customerIds)
            ->select([
                'id',
                'first_name',
                'last_name',
                'code as ref_id',
                'uuid',
                'quote_type_id',
                'customer_id',
                'quote_status_id',
                'source',
                'advisor_id',
                'created_at',
            ])
            ->orderBy('created_at', 'desc')
            ->simplePaginate()->withQueryString();
    }
}
