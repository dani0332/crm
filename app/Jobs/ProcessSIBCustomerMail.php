<?php

namespace App\Jobs;

use App\Services\CustomerService;
use App\Services\CustomerWEGenerateUrlService;
use App\Services\SendEmailCustomerService;
use Error;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        }
    }
}
