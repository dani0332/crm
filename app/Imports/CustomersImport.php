<?php

namespace App\Imports;

use App\Models\Customer;
use App\Services\CustomerService;
use App\Jobs\MailServiceJob;
use Maatwebsite\Excel\Concerns\ToModel;

class CustomersImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $email = $row[1];
        $customerName = explode(" ", $row[0], 2);
        $lastName = "";
        if (!empty($customerName[1])) {
            $firstName = $customerName[0];
            $lastName = $customerName[1];
        }
        else {
            $firstName = $row[0];
            $lastName = "";
        }

        $findCustomerByEmail = CustomerService::getCustomerByEmail($email);
        if($findCustomerByEmail->first()) {
            return;
        }
        else {
            $newCustomer = new Customer([
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $email,
                "has_alfred_access" => true,
                "has_reward_access" => true,
            ]);

            // $newCustomer->save();

            // $params = ["customerName" => $firstName.' '.$lastName];
            // $emailPayload = (object) [
            //     'to' => $email,
            //     'subject' => 'Welcome to myAlfred by InsuranceMarket.ae',
            //     'templateName' => 'customerWelcome',
            //     'templateParams' => $params,
            // ];

            // dispatch(new MailServiceJob(json_encode($emailPayload)));

            // $createdCustomer = Customer::where('id', '=', $newCustomer->id);
            // $createdCustomer->is_we_sent = true;
            // $createdCustomer->save();

            return $newCustomer;
        }
    }
}
