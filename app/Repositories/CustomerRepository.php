<?php

namespace App\Repositories;

use App\Enums\GenericRequestEnum;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;
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

    public function fetchMakeAdditionalContactPrimary($quoteObject, $request) {
        $_return = true;
        try {
            DB::beginTransaction();
            if ($request->key == GenericRequestEnum::EMAIL) {
                Log::info('Customer additional contact primary email updated. Previous Email: '.$quoteObject->email.' New Email: '.$request->value);
                $quoteObject->email = $request->value;
                $customerService = new CustomerService();
                $customer = $customerService::getCustomerByEmail($request->value);
                if ($customer) {
                    $quoteObject->customer_id = $customer->id;
                    if (isset($request->quote_primary_email_address) && isset($request->quote_customer_id)) {
                        CustomerAdditionalContact::updateOrCreate([
                            'customer_id' => $request->quote_customer_id,
                            'key' => GenericRequestEnum::EMAIL,
                            'value' => strtolower($request->quote_primary_email_address),
                        ]);

                        // Replicate Old additional contact info with new customer
                        CustomerRepository::replicatePreviousAdditionalContacts($request->quote_customer_id, $customer->id);
                    }
                } else {
                    // Move current customer to additional contacts if not exists
                    CustomerAdditionalContact::updateOrCreate([
                        'customer_id' => $request->quote_customer_id,
                        'key' => GenericRequestEnum::EMAIL,
                        'value' => strtolower($request->quote_primary_email_address),
                    ]);

                    $customer = Customer::create([
                        'first_name' => $quoteObject->first_name,
                        'last_name' => $quoteObject->last_name,
                        'mobile_no' => $quoteObject->mobile_no,
                        'email' => $request->value,
                    ]);

                    $quoteObject->customer_id = $customer->id;

                    // Replicate Old additional contact info with new customer
                    CustomerRepository::replicatePreviousAdditionalContacts($request->quote_customer_id, $customer->id);
                }
            } elseif ($request->key == GenericRequestEnum::MOBILE_NO) {
                Log::info('Customer additional contact primary mobile_no updated. Previous Mobile_No: '.$quoteObject->mobile_no.' New Mobile_No: '.$request->value);
                $quoteObject->mobile_no = $request->value;
                if (isset($request->quote_primary_mobile_no) && isset($request->quote_customer_id)) {
                    CustomerAdditionalContact::updateOrCreate([
                        'customer_id' => $request->quote_customer_id,
                        'key' => 'mobile_no',
                        'value' => trim($request->quote_primary_mobile_no),
                    ]);
                }
            }

            $quoteObject->save();
            DB::commit();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollback();
            $_return = false;
        }

        return $_return;
    }
}
