<?php

namespace App\Jobs;

use App\Models\MyAlFredUser;
use App\Services\CustomerService;
use App\Services\CustomerWEGenerateUrlService;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class ProcessSIBCustomerMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $name;

    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function handle()
    {
        $emailTemplateId = (int)Config::get('constants.SIB_CORPORATE_TEMPLATE');
        $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl($this->email);

        $emailData = array(
            'customerName' => $this->name,
            'customerEmail' => $this->email,
            'signUpButtonUrl' => $WEGenerateUrlResponse
        );

        $getStatusCode = SendEmailCustomerService::sendEmail($emailTemplateId, $emailData, $tag='corporate-myalfred-we');

        if($getStatusCode == 201) {
            $findCustomerByEmail = CustomerService::getCustomerByEmail($this->email);
            $updateCustomer = $findCustomerByEmail->first();
            $updateCustomer->is_we_sent = true;
            $updateCustomer->save();

            $code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, "signup/") + 7);
            $newMyAlFredUser = new MyAlFredUser;
            $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
            $newMyAlFredUser->customer_id = $updateCustomer->id;
            $newMyAlFredUser->code = $code;
            $newMyAlFredUser->source = "CORPORATE";
            $newMyAlFredUser->save();
        }
    }
}
