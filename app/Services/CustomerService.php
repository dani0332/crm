<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use Illuminate\Support\Facades\Log;

class CustomerService extends BaseService
{
    public static function getCustomerByEmail($email)
    {
        return Customer::where('email', $email)->get();
    }

    public static function getUniqueCustomerByEmail($email)
    {
        return Customer::where('email', $email)->first();
    }

    public static function getUniqueCustomerByMobileNo($mobileNo)
    {
        return Customer::where('mobile_no', $mobileNo)->first();
    }

    public static function updatePolicyExpiry($email, $expiry_date)
    {
        //Log::info('Inside updatePolicyExpiry');
        $customer = Customer::where('email', $email)->get()->first();
        //Log::info('Inside updatePolicyExpiry customer found');
        $parsedPolicyExpiry = date('Y-m-d', strtotime(str_replace('.', '-', $expiry_date)));
        $parsedCustomerExpiry = date('Y-m-d', strtotime($customer->myalfred_expiry_date));
        if ($parsedCustomerExpiry < $parsedPolicyExpiry) {
            $customer->myalfred_expiry_date = $parsedPolicyExpiry;
            $customer->save();
        //Log::info('Inside updatePolicyExpiry record updated');
        } else {
            //Log::info('Inside updatePolicyExpiry record date is already newer than policy date');
        }
    }

    public static function getCustomerById($customerId)
    {
        return Customer::where('id', $customerId)->get()->first();
    }

    public static function getCustomerIdAndCreateIfNotExists($firstName, $lastName, $email)
    {
        $customer = self::getCustomerByEmail($email);
        if ($customer->first()) {
            return $customer->first()->id;
        } else {
            return self::createCustomerAndGetId($firstName, $lastName, $email);
        }
    }

    public static function createCustomerAndGetId($firstName, $lastName, $email)
    {
        //Log::info('creating customer inside customer service');
        $existingCustomer = Customer::where('email', $email)->get()->first();
        if ($existingCustomer == '') {
            $customer = new Customer();
            $customer->first_name = $firstName;
            $customer->last_name = $lastName;
            $customer->email = strtolower($email);
            $customer->lang = 'EN';
            $customer->save();

            return $customer->id;
        } else {
            //Log::info('Customer found in database inside create customer method');
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

    public function getAddtionalContacts($customerId)
    {
        return CustomerAdditionalContact::where('customer_id', $customerId)
        ->orderBy('created_at', 'desc')->get();
    }

    public function checkAdditionalEmailExist($quoteObject, $newAdditionalEmail)
    {
        $newAdditionalEmail = strtolower($newAdditionalEmail);
        $customer = $this->getUniqueCustomerByEmail($newAdditionalEmail);
        $additionalEmail = CustomerAdditionalContact::where(['key' => GenericRequestEnum::EMAIL, 'value' => $newAdditionalEmail])
        ->first();

        if ($newAdditionalEmail == strtolower($quoteObject->email)
            || $customer && $newAdditionalEmail == strtolower($customer->email)
            || $additionalEmail && $newAdditionalEmail == strtolower($additionalEmail->value)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkAdditionalMobileNoExist($quoteObject, $newAdditionalMobileNo)
    {
        $customer = $this->getUniqueCustomerByMobileNo($newAdditionalMobileNo);
        $additionalMobileNo = CustomerAdditionalContact::where(['key' => GenericRequestEnum::MOBILE_NO, 'value' => $newAdditionalMobileNo])
        ->first();

        if ($newAdditionalMobileNo == $quoteObject->mobile_no
        || $customer && $newAdditionalMobileNo == $customer->mobile_no
        || $additionalMobileNo && $newAdditionalMobileNo == $additionalMobileNo->value) {
            return true;
        } else {
            return false;
        }
    }
}
