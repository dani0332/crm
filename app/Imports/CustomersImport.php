<?php

namespace App\Imports;

use App\Jobs\MAWelcomeJob;
use App\Models\Customer;
use App\Models\QuoteCustomer;
use App\Services\BerlinService;
use App\Services\CustomerService;
use App\Services\SendEmailCustomerService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class CustomersImport implements OnEachRow
{
    public $myalfredExpiryDate;
    public $CDBId;
    public $inviatationEmail;
    public $sendEmailCustomerService;
    public $berlinService;

    public function __construct(
        $myalfredExpiryDate,
        $cdbId,
        $inviatationEmail,
        SendEmailCustomerService $sendEmailCustomerService,
        BerlinService $berlinService,
    ) {
        $this->myalfredExpiryDate = $myalfredExpiryDate;
        $this->CDBId = $cdbId;
        $this->inviatationEmail = $inviatationEmail;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->berlinService = $berlinService;
    }

    /**
     * @param  array  $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function onRow(Row $row)
    {
        if ($row->getIndex() == 1) {
            return null;
        }

        $row = $row->toArray();

        $email = $row[1];

        if ($email != null && isValidEmail($email)) {
            $customerId = 0;
            $myalfredExpiryDate = date('Y-m-d H:i:s', strtotime(str_replace('"', '', $this->myalfredExpiryDate)));
            $customerName = explode(' ', $row[0], 2);
            $lastName = '';
            if (! empty($customerName[1])) {
                $firstName = $customerName[0];
                $lastName = $customerName[1];
            } else {
                $firstName = $row[0];
                $lastName = '';
            }

            $updateCustomer = CustomerService::getCustomerByEmail($email);
            if ($updateCustomer) {
                $updateCustomer->first_name = $firstName;
                $updateCustomer->last_name = $lastName;
                $updateCustomer->has_alfred_access = true;
                $updateCustomer->has_reward_access = true;
                if ($updateCustomer->myalfred_expiry_date < $myalfredExpiryDate) {
                    $updateCustomer->myalfred_expiry_date = $myalfredExpiryDate;
                }
                $updateCustomer->save();
            } else {
                $updateCustomer = new Customer([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => strtolower(trim($email)),
                    'has_alfred_access' => true,
                    'has_reward_access' => true,
                    'myalfred_expiry_date' => $myalfredExpiryDate,
                ]);
                $updateCustomer->save();
            }
            MAWelcomeJob::dispatch($updateCustomer, 'CORPORATE', 'corporate-myalfred-we');

            $newQuoteCustomer = new QuoteCustomer;
            $newQuoteCustomer->cdb_id = $this->CDBId;
            $newQuoteCustomer->customer_id = $customerId;
            $newQuoteCustomer->save();
            Log::info('Saved in quote customer with Customer Id-> '.$customerId.' , Ref-ID ->'.$this->CDBId);
        }
    }
}
