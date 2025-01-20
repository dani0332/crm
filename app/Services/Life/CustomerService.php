<?php

namespace App\Services\Life;

use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Services\BaseService;

class CustomerService extends BaseService
{
    public function getAdditionalContacts($customerId, $quoteMobileNo)
    {
        $customer = Customer::where('id', $customerId)->first();
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
