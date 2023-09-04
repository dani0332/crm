<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use Illuminate\Support\Facades\Log;

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
     * @return CustomerAdditionalContact
     */
    public function fetchStoreAdditionalContact($customerId, $data)
    {
        Log::info('Customer additional contact id: '.$customerId.' new: '.$data['key'].' value: '.$data['value']);

        return CustomerAdditionalContact::updateOrCreate([
            'customer_id' => $customerId,
            'key' => $data['key'],
            'value' => trim($data['value']),
        ]);
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
}
