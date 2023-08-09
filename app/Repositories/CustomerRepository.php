<?php

namespace App\Repositories;

use App\Http\Requests\CustomerUploadRequest;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
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
        $query = [];
        $filterColumns = ['email'];

        if ((!empty(request()->get('search_type')) && !empty(request()->get('search_value'))) &&
            in_array(request()->get('search_type'), $filterColumns)){
            $query = $this->where(request()->get('search_type'), request()->get('search_value'))
                ->orderBy('created_at', 'desc')->simplePaginate();
        }

        return $query;

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
        if($customerUploadRequest->hasFile('file_name')){
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
