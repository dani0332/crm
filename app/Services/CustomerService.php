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

    public function getAdditionalContactByKey($customerId, $key)
    {
        return CustomerAdditionalContact::where(['customer_id' => $customerId, 'key' => $key])->get();
    }

    public static function getCustomerByUuid($uuid)
    {
        return Customer::where('uuid', $uuid)->first();
    }
}
