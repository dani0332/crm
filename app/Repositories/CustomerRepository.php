<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerAdditionalContact;

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
}
