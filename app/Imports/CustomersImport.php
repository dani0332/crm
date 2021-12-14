<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\QuoteCustomer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Http;


class CustomersImport implements OnEachRow, WithStartRow
{

    public $myalfredExpiryDate;
    public $CDBId;
    public $inviatationEmail;
    public function __construct($myalfredExpiryDate, $cdbId, $inviatationEmail)
    {
        $this->myalfredExpiryDate = $myalfredExpiryDate;
        $this->CDBId = $cdbId;
        $this->inviatationEmail = $inviatationEmail;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function onRow(Row $row)
    {
        Log::channel('daily')->info('Entered in Excel Import per row');
        $row = $row->toArray();

        $email = $row[1];

        if($email != null) {
            $customerId = 0;
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
            function sendEmail($email, $name) {
                $apiKey = env('SENDINBLUE_KEY');
                $url = env('SIB_URL');
                $sibTemplate = env('SIB_CORPORATE_TEMPLATE');
        
                $headers = [
                    'Accept' => 'application/json',
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json'
                ];
        
                $body = [
                    "to" => array([
                        "email" => $email,
                        "name" => $name,
                    ]),
                    "templateId" => $sibTemplate,
                ];
        
                Http::withHeaders($headers)->post($url, $body);
            }
            if ($this->inviatationEmail == 'on') {
                sendEmail($email, $firstName);
            }
            $findCustomerByEmail = CustomerService::getCustomerByEmail($email);
            if(!$findCustomerByEmail->isEmpty()) {
                $updateCustomer = $findCustomerByEmail->first();
                $updateCustomer->first_name = $firstName;
                $updateCustomer->last_name = $lastName;
                $updateCustomer->has_alfred_access = true;
                $updateCustomer->has_reward_access = true;
                if($updateCustomer->myalfred_expiry_date < $myalfredExpiryDate){
                    $updateCustomer->myalfred_expiry_date = $myalfredExpiryDate;
                }
                $updateCustomer->save();
                $customerId = $updateCustomer->id;
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
                $customerId = $newCustomer->id;
            }
            $existingQuoteCustomer = QuoteCustomer::where([['customer_id', '=', $customerId], ['cdb_id', '=', $this->CDBId]])->get();
            if($existingQuoteCustomer->isEmpty()) {
                $newQuoteCustomer = new QuoteCustomer();
                $newQuoteCustomer->cdb_id = $this->CDBId;
                $newQuoteCustomer->customer_id = $customerId;
                $newQuoteCustomer->save();
                Log::channel('daily')->info('Saved in quote customer with Customer Id-> '.$customerId.' , CDB Id ->'. $this->CDBId);
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }

}
