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
use DB;
use Exception;

class ProcessSIBCustomerMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $name;
    protected $sendEmailCustomerService;

    public function __construct($email, $name, SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->email = $email;
        $this->name = $name;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function handle()
    {
        try {
            $emailTemplateId = (int)Config::get('constants.SIB_CORPORATE_TEMPLATE');
            $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl($this->email);

            $emailData = array(
                'customerName' => $this->name,
                'customerEmail' => $this->email,
                'signUpButtonUrl' => $WEGenerateUrlResponse
            );

            $getStatusCode = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'corporate-myalfred-we');

            if ($getStatusCode == 201) {
                $findCustomerByEmail = CustomerService::getCustomerByEmail($this->email);
                $updateCustomer = $findCustomerByEmail->first();
                $updateCustomer->is_we_sent = true;
                $updateCustomer->save();

                $existingCustomer = MyAlFredUser::where('customer_id', '=', $updateCustomer->id)->get();

                if ($existingCustomer->isEmpty()) {
                    $code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, "signup/") + 7);
                    $newMyAlFredUser = new MyAlFredUser;
                    $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
                    $newMyAlFredUser->customer_id = $updateCustomer->id;
                    $newMyAlFredUser->code = $code;
                    $newMyAlFredUser->source = "CORPORATE";
                    $newMyAlFredUser->save();
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        } finally {
            DB::disconnect('mysql');
        }
    }
}
