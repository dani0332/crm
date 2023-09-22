<?php

namespace App\Repositories;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Http\Requests\CustomerUploadRequest;
use App\Imports\CustomersImport;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\SendEmailCustomerService;
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

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        $allQuotes = [];
        $filterValue = request()->get('search_value');
        $filterType = request()->get('search_type');
        $filterColumns = ['email', 'first_name', 'mobile_no', 'uuid', 'insured_first_name'];
//        $filterColumns = ['email', 'first_name', 'mobile_no', 'uuid', 'insured_first_name', 'entity_name'];

        if ( in_array($filterType, $filterColumns) && (!empty($filterType) && !empty($filterValue)) ) {

//            $customer = Customer::where($filterType, $filterValue)->firstOrFail();

            $carQuotes = CarQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Car.'" as quote_type_id') ])
                ->orderBy('created_at', 'desc');

            $healthQuotes = HealthQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Health.'" as quote_type_id') ])
                ->orderBy('created_at', 'desc');

            $travelQuotes = TravelQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Travel.'" as quote_type_id') ])
                ->orderBy('created_at', 'desc');

            $lifeQuotes = LifeQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Life.'" as quote_type_id') ])
                ->orderBy('created_at', 'desc');

            $homeQuotes = HomeQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Home.'" as quote_type_id') ])
                ->orderBy('created_at', 'desc');

            $personalQuotes = PersonalQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date', 'quote_type_id'])
                ->orderBy('created_at', 'desc');

            $businessQuotes = BusinessQuote::with(['advisor', 'customer'])
                ->whereHas('customer', function ($customer) use ($filterType, $filterValue){
                    $customer->where($filterType, $filterValue);
                })
                ->where([
                    'quote_status_id' => QuoteStatusEnum::TransactionApproved,
//                    'customer_id' => $customer->id
                ])
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'renewal_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Business.'" as quote_type_id') ])
                ->orderBy('created_at', 'desc');

            $allQuotes =  $personalQuotes
                ->union($healthQuotes)
                ->union($lifeQuotes)
                ->union($travelQuotes)
                ->union($homeQuotes)
                ->union($businessQuotes)
                ->union($carQuotes)->simplePaginate();

//            $allQuotes = collect($allQuotes->items())->map(function ($item) use ($customer) {
//                return collect($item)->merge(['customer' => $customer]);
//            });

            return $allQuotes;

        }

        return $allQuotes;

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
        $customer = $this->findOrFail($customerId);
        $customer->additionalContactInfo()->create($data);

        return $customer;
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

    public function fetchCustomerUploadRecordsCreate(CustomerUploadRequest $customerUploadRequest, SendEmailCustomerService $sendEmailCustomerService)
    {
        if ($customerUploadRequest->hasFile('file_name')) {
            return Excel::import(new CustomersImport(
                $customerUploadRequest->myalfred_expiry_date,
                $customerUploadRequest->cdb_id,
                $customerUploadRequest->inviatation_email,
                $sendEmailCustomerService
            ), $customerUploadRequest->file('file_name'));
        }

        vAbort('Something went wrong while uploading');
    }
}
