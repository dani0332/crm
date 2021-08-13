<?php

namespace App\Services;
use App\Models\Customer;


class CustomerService extends BaseService
{

	public static function getCustomerByEmail($email)
	{
		return Customer::where('email', '=', $email)->get();
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
        $customer = new Customer();
        $customer->first_name = $firstName;
        $customer->last_name = $lastName;
        $customer->email = $email;
        $customer->lang = 'EN';
        $customer->save();
        $customerId = Customer::where('email', '=', $email)->get()->first()->id;
        return $customerId;
	}


    public static function setCustomerAccess($customerId)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $customer->has_alfred_access = true;
        $customer->has_reward_access = true;
        $customer->save();
    }
}
