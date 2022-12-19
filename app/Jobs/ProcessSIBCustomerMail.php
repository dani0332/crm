<?php

namespace App\Jobs;

use App\Models\MyAlFredUser;
use App\Services\CustomerService;
use App\Services\CustomerWEGenerateUrlService;
use App\Services\SendEmailCustomerService;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSIBCustomerMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $name;
    protected $sendEmailCustomerService;
    protected $customerWEGenerateUrlService;

    public function __construct($email, $name, SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->email = $email;
        $this->name = $name;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function handle(CustomerWEGenerateUrlService $customerWEGenerateUrlService)
    {
        info('ProcessSIBCustomerMail handle START');
        try {
            $this->customerWEGenerateUrlService = $customerWEGenerateUrlService;
            $emailTemplateId = (int) config('constants.SIB_CORPORATE_TEMPLATE');
            $myAlfredSignupUrl = $this->customerWEGenerateUrlService->getCustomerWeUrl($this->email);

            $emailData = (object) [
                'customerName' => $this->name,
                'customerEmail' => $this->email,
                'signUpButtonUrl' => $myAlfredSignupUrl,
            ];

            $getStatusCode = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'corporate-myalfred-we');

            info('ProcessSIBCustomerMail data: '.json_encode($emailData).' , emailTemplateId:'.$emailTemplateId);

            if ($getStatusCode == 201) {
                info('MyAlfred welcome email sent to coporate customer '.$this->email);
                $customer = CustomerService::getCustomerByEmail($this->email);
                if ($customer) {
                    $updateCustomer = $customer->first();
                    $updateCustomer->is_we_sent = true;
                    $updateCustomer->save();

                    $myAlfredUser = MyAlFredUser::where('customer_id', $updateCustomer->id)->get();
                    if ($myAlfredUser->isEmpty()) {
                        $newMyAlfredUser = new MyAlFredUser;
                        $newMyAlfredUser->signup_url = $myAlfredSignupUrl;
                        $newMyAlfredUser->customer_id = $updateCustomer->id;
                        $newMyAlfredUser->code = substr($myAlfredSignupUrl, strpos($myAlfredSignupUrl, 'signup/') + 7);
                        $newMyAlfredUser->source = 'CORPORATE';
                        $newMyAlfredUser->save();
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('ProcessSIBCustomerMail Error - Customer Email: '.$this->email.' Message: '.$e->getMessage());

            return $e->getMessage();
        } finally {
            DB::disconnect('mysql');
        }
        info('ProcessSIBCustomerMail handle End');
    }
}
