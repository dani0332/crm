<?php

namespace App\Imports;

use App\Models\Customer;
use App\Services\CustomerService;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

class CustomersImport implements OnEachRow
{

    public $myalfredExpiryDate;
    public function __construct($myalfredExpiryDate)
    {   
        $this->myalfredExpiryDate = $myalfredExpiryDate;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function onRow(Row $row)
    {
        $row = $row->toArray();
        
        $email = $row[1];
        $myalfredExpiryDate = date('Y-m-d H:i:s', strtotime(str_replace('"', '', $this->myalfredExpiryDate)));
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

        if($row[0] != 'Customer Name') {
            $findCustomerByEmail = CustomerService::getCustomerByEmail($email);
            if($findCustomerByEmail->first()) {
                $updateCustomer = $findCustomerByEmail->first();
                $updateCustomer->first_name = $firstName;
                $updateCustomer->last_name = $lastName;
                $updateCustomer->myalfred_expiry_date = $myalfredExpiryDate;
                $updateCustomer->save();
                return;
            }
            else {
                $newCustomer = new Customer([
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                    "email" => $email,
                    "has_alfred_access" => true,
                    "has_reward_access" => true,
                    "myalfred_expiry_date" => $myalfredExpiryDate,
                ]);
                $newCustomer->save();
            }
        }
    }
}
