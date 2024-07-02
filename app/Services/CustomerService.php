<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;

class CustomerService extends BaseService
{
    public static function getCustomerByEmail($email)
    {
        return Customer::where('email', strtolower(trim($email)))->first();
    }

    public static function getCustomerByName($first_name, $last_name)
    {
        return Customer::where('first_name', strtolower(trim($first_name)))
            ->where('last_name', strtolower(trim($last_name)))
            ->first();
    }

    public static function getUniqueCustomerByMobileNo($mobileNo)
    {
        return Customer::where('mobile_no', $mobileNo)->first();
    }

    public static function updatePolicyExpiry($email, $expiry_date)
    {
        $customer = Customer::where('email', strtolower(trim($email)))->first();
        $parsedPolicyExpiry = date('Y-m-d', strtotime(str_replace('.', '-', $expiry_date)));
        $parsedCustomerExpiry = date('Y-m-d', strtotime($customer->myalfred_expiry_date));
        if ($parsedCustomerExpiry < $parsedPolicyExpiry) {
            $customer->myalfred_expiry_date = $parsedPolicyExpiry;
            $customer->save();
        }
    }

    public static function getCustomerById($customerId)
    {
        return Customer::where('id', $customerId)->first();
    }

    public static function getCustomerIdAndCreateIfNotExists($firstName, $lastName, $email)
    {
        $customer = self::getCustomerByEmail($email);
        if ($customer) {
            return $customer->id;
        } else {
            return self::createCustomerAndGetId($firstName, $lastName, $email);
        }
    }

    public static function createCustomerAndGetId($firstName, $lastName, $email)
    {
        $existingCustomer = Customer::where('email', strtolower(trim($email)))->first();
        if (! $existingCustomer) {
            $customer = Customer::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower(trim($email)),
                'lang' => 'EN',
            ]);

            return $customer->id;
        } else {
            return false;
        }
    }

    public static function setCustomerAccess($customerId)
    {
        $customer = self::getCustomerById($customerId);
        $customer->has_alfred_access = true;
        $customer->has_reward_access = true;
        $customer->save();
    }

    public static function getAllCustomers($from, $to)
    {
        $from = date($from);
        $to = date($to);

        return Customer::whereBetween('created_at', [$from, $to])
            ->where(['has_alfred_access' => 1, 'has_reward_access' => 1])
            ->get();
    }

    public static function getValidEmailFromString($emailStr)
    {
        $customer_email = $emailStr;
        if (strpos($emailStr, ',')) {
            $strArray = explode(',', $emailStr);
            $customer_email = $strArray[0];
        }
        if (strpos($emailStr, ';')) {
            $strArray = explode(';', $emailStr);
            $customer_email = $strArray[0];
        }

        return $customer_email;
    }

    public function getAdditionalContacts($customerId, $quoteMobileNo)
    {
        $customer = $this->getCustomerById($customerId);
        $additionalContacts = CustomerAdditionalContact::where('customer_id', $customerId)->orderBy('created_at', 'desc')->get();

        if (isset($customer) && $quoteMobileNo != $customer->mobile_no && $additionalContacts->where('key', 'mobile_no')->where('value', $customer->mobile_no)->isEmpty()) {
            $customerMobileNo = (object) [
                'key' => 'mobile_no',
                'value' => isset($customer->mobile_no) ? $customer->mobile_no : '',
                'created_at' => isset($customer->created_at) ? $customer->created_at : '',
            ];
            $additionalContacts->push($customerMobileNo);
        }

        return $additionalContacts;
    }

    public function checkAdditionalEmailExist($lead, $newAdditionalEmail)
    {
        $newAdditionalEmail = strtolower($newAdditionalEmail);
        $customer = $this->getCustomerByEmail($newAdditionalEmail);
        $additionalEmail = CustomerAdditionalContact::where(['key' => GenericRequestEnum::EMAIL, 'value' => $newAdditionalEmail])
            ->first();

        if ($newAdditionalEmail == strtolower($lead->email)
            || $customer && $newAdditionalEmail == strtolower($customer->email)
            || $additionalEmail && $newAdditionalEmail == strtolower($additionalEmail->value)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkAdditionalMobileNoExist($lead, $newAdditionalMobileNo)
    {
        $customer = $this->getUniqueCustomerByMobileNo($newAdditionalMobileNo);
        $additionalMobileNo = CustomerAdditionalContact::where(['key' => GenericRequestEnum::MOBILE_NO, 'value' => $newAdditionalMobileNo])
            ->first();

        if ($newAdditionalMobileNo == $lead->mobile_no
        || $customer && $newAdditionalMobileNo == $customer->mobile_no
        || $additionalMobileNo && $newAdditionalMobileNo == $additionalMobileNo->value) {
            return true;
        } else {
            return false;
        }
    }

    public function getAdditionalContactByKey($customerId, $key)
    {
        return CustomerAdditionalContact::where(['customer_id' => $customerId, 'key' => $key])->get();
    }

    public static function getCustomerByUuid($uuid)
    {
        return Customer::where('uuid', $uuid)->first();
    }

    public function makeAdditionalContactPrimary($lead, $key, $value)
    {
        if ($key == GenericRequestEnum::EMAIL) {
            if ($lead->customer && ! $this->getCustomerByEmail($value)) {
                info('Customer additional contact primary email updated. Previous Email: '.$lead->email.' New Email: '.$value);
                $customerArray = [
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'mobile_no' => $lead->mobile_no,
                    'email' => $value,
                    'dob' => $lead->dob,
                ];
                $customer = Customer::create($customerArray);
                $customer->update(['code' => 'IND-'.$customer->id]);
                $getCustomerAdditionalContact = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                    ->get();
                foreach ($getCustomerAdditionalContact as $contact) {
                    CustomerAdditionalContact::create([
                        'customer_id' => $customer->id,
                        'key' => $contact->key,
                        'value' => $contact->value,
                    ]);
                }
                $email = trim($lead->email);

                // Check if the email ends with the specified domains
                if (! str_ends_with($email, '@insurancemarket.ae') && ! str_ends_with($email, '@afia.ae')) {
                    $isExist = CustomerAdditionalContact::where('key', 'email')
                        ->where('customer_id', $lead->customer_id)
                        ->where('value', $email)
                        ->exists();

                    if (! $isExist) {
                        CustomerAdditionalContact::create([
                            'customer_id' => $customer->id,
                            'key' => 'email',
                            'value' => $email,
                        ]);
                    }
                }
                $lead->update(['customer_id' => $customer->id, 'email' => $value]);

                // REMOVE EMAIL TO MAKE PRIMARY IN ADDITIONAL CONTACT
                $removeEmail = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                    ->where('value', $lead->email)
                    ->where('key', 'email')
                    ->first();
                if (isset($removeEmail->id)) {
                    $removeEmail->delete();
                }
                //ADD PRIMARY EMAIL IN ADDITIONAL CONTACT
                $removeAdvisorEmail = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                    ->where('key', 'email')
                    ->where(function ($query) {
                        $query->where('value', 'like', '%@insurancemarket.ae')
                            ->orWhere('value', 'like', '%@afia.ae');
                    })
                    ->first();
                if (isset($removeAdvisorEmail->id)) {
                    $removeAdvisorEmail->delete();
                }
            } else {
                // REMOVE EMAIL TO MAKE PRIMARY IN ADDITIONAL CONTACT
                $removeEmail = CustomerAdditionalContact::where('value', $value)
                    ->where('customer_id', $lead->customer_id)
                    ->where('key', 'email')
                    ->first();
                if (isset($removeEmail->id)) {
                    $removeEmail->delete();
                }
                $customer = $this->getCustomerByEmail($value);

                CustomerAdditionalContact::firstOrCreate([
                    'customer_id' => $customer->id,
                    'key' => GenericRequestEnum::EMAIL,
                    'value' => trim($lead->email),
                ]);

                $getCustomerAdditionalContact = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                    ->get();
                foreach ($getCustomerAdditionalContact as $contact) {
                    CustomerAdditionalContact::firstOrCreate([
                        'customer_id' => $customer->id,
                        'key' => $contact->key,
                        'value' => trim($contact->value),
                    ]);
                }
                $lead->update(['customer_id' => $customer->id, 'email' => $value]);
            }
        } elseif ($key == GenericRequestEnum::MOBILE_NO) {
            $lead->update(['mobile_no' => $value]);
            if ($lead->customer) {
                info('Customer additional contact primary mobile_no updated. Previous Mobile_No: '.$lead->mobile_no.' New Mobile_No: '.$value);
                $lead->customer->update(['mobile_no' => $value]);
            }
        }
    }
}
