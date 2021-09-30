<?php

namespace App\Services;
use App\Models\Customer;
use DB;
use Illuminate\Support\Facades\Log;

class CustomerService extends BaseService
{

	public static function getCustomerByEmail($email)
	{
		return Customer::where('email', '=', $email)->get();
	}

    public static function updatePolicyExpiry($email, $expiry_date)
	{
        Log::channel('daily')->info('Inside updatePolicyExpiry');
		$customer = Customer::where('email', '=', $email)->get()->first();
        Log::channel('daily')->info('Inside updatePolicyExpiry customer found');
        $parsedPolicyExpiry = date('Y-m-d', strtotime(str_replace('.', '-', $expiry_date)));
        $parsedCustomerExpiry = date('Y-m-d', strtotime($customer->myalfred_expiry_date));
        if($parsedCustomerExpiry < $parsedPolicyExpiry){
            $customer->myalfred_expiry_date = $parsedPolicyExpiry;
            $customer->save();
            Log::channel('daily')->info('Inside updatePolicyExpiry record updated');
        }else{
            Log::channel('daily')->info('Inside updatePolicyExpiry record date is already newer than policy date');
        }
	}

    public static function getCustomerById($customerId)
    {
        return Customer::where('id', '=', $customerId)->get()->first();
    }

	public static function getCustomerIdAndCreateIfNotExists($firstName, $lastName, $email)
	{
		$customer = CustomerService::getCustomerByEmail($email);
		if($customer->first()) return $customer->first()->id;
		else return CustomerService::createCustomerAndGetId($firstName, $lastName, $email);
	}

    public static function createCustomerAndGetId($firstName, $lastName, $email)
	{
        Log::channel('daily')->info('creating customer inside customer service');
        $existingCustomer = Customer::where('email', $email)->get()->first();
        if($existingCustomer == ''){
            $customer = new Customer();
            $customer->first_name = $firstName;
            $customer->last_name = $lastName;
            $customer->email = strtolower($email);
            $customer->lang = 'EN';
            $customer->save();
            return $customer->id;
        }else{
            Log::channel('daily')->info('Customer found in database inside create customer method');
        }
	}


    public static function setCustomerAccess($customerId)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $customer->has_alfred_access = true;
        $customer->has_reward_access = true;
        $customer->save();
    }

    public static function getAllCustomers($from, $to) {
        $from = date($from);
        $to = date($to);
        return Customer::whereBetween('created_at', [$from, $to])
            ->where([ 'has_alfred_access' => 1, 'has_reward_access' => 1 ])
            ->get();
    }

    public static function getValidEmailFromString($emailStr){
        $customer_email = $emailStr;
        if(strpos($emailStr, ',')){
            $strArray = explode(',' , $emailStr);
            $customer_email = $strArray[0];
        }
        if(strpos($emailStr, ';')){
            $strArray = explode(';' , $emailStr);
            $customer_email = $strArray[0];
        }
        return $customer_email;
    }
}
