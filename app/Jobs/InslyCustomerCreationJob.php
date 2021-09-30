<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CustomerService;
use App\Services\InslyDataService;
use Exception;
use Illuminate\Support\Facades\Log;

class InslyCustomerCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $policies;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->policies = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach((array)$this->policies as $policy){
            $customer_name = $policy->customer_name;
            $customer_email = preg_replace('/\s+/', '', $policy->customer_email);
            $customer_email = strtolower($customer_email);
            if(strpos($customer_email, '@') > 0 && strlen($customer_name) > 0) {
                $getValidEmail = CustomerService::getValidEmailFromString($customer_email);

                $customer_email = $getValidEmail != $customer_email ? $getValidEmail : $customer_email;

                Log::channel('daily')->info('Initiating process for customer with email: '.$customer_email);

                $customer = CustomerService::getCustomerByEmail($customer_email)->first();
                $customerId = 0;
                if($customer != '') { // If customer already exists in our database
                    Log::channel('daily')->info('Customer with email: '.$customer_email.' found in database');

                    $customer->has_reward_access = true;
                    $customer->has_alfred_access = true;

                    $customer->save();
                } else { // If customer doesn't exist in our database
                    Log::channel('daily')->info('Customer with email: '.$customer_email.' not found in database');
                    $first_name = ''; $last_name = '';
                    $customer_name = trim($customer_name);
                    if (!strpos($customer_name, ' ')) {
                        $first_name = $customer_name;
                    }
                    else{
                        list($first_name, $last_name) = explode(' ', $customer_name,2);
                    }

                    try{
                        $customerId = CustomerService::createCustomerAndGetId($first_name, $last_name, $customer_email);
                        CustomerService::setCustomerAccess($customerId); // enabling has_alfred_access and has_reward_access for newly created customer
                    } catch(Exception $ex){
                        Log::channel('daily')->info($ex->__toString());
                        Log::channel('daily')->info('Failed to create customer with email: '.$customer_email);
                    }

                }
                Log::channel('daily')->info('Initiating complete insly data insertion in database table');
                InslyDataService::AddInslyRecordInDatabase($customer_name, $customer_email, $policy, $customerId == 0 ? true : false);

                Log::channel('daily')->info('Initiating update policy expiry update process');
                CustomerService::updatePolicyExpiry($customer_email, $policy->policy_date_end);
            }
            else{
                Log::channel('daily')->info('Customer Email OR Name is empty so adding data into the insly mapping with flag true');
                Log::channel('daily')->info('Customer Email : '.$customer_email.' Customer Name : '.$customer_name);
                InslyDataService::AddInslyRecordInDatabase($customer_name, $customer_email, $policy, true);
            }
        }
    }
}
