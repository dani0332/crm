<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\MyAlFredUser;
use App\Services\BerlinService;
use App\Services\CustomerService;
use App\Services\SendEmailCustomerService;
use App\Services\SendSmsCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class MAWelcomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 20;
    private $source;
    private $tag;
    private Customer $customer;

    public function __construct(Customer $customer, $source, $tag)
    {
        $this->customer = $customer;
        $this->source = $source;
        $this->tag = $tag;
    }

    public function handle(BerlinService $berlinService, SendEmailCustomerService $sendEmailCustomerService, SendSmsCustomerService $sendSmsCustomerService)
    {
        if (! $this->customer) {
            info('MAWelcomeJob - Error - Empty Customer Object');

            return false;
        }
        $customerInviteCode = $berlinService->getCustomerInviteCode();
        $data = (object) [
            'customerFirstName' => $this->customer->first_name,
            'customerLastName' => $this->customer->last_name,
            'customerEmail' => $this->customer->email,
            'inviteCode' => $customerInviteCode,
        ];
        try {
            $statusCode = $sendEmailCustomerService->sendMyAlfredWelcomeEmail($data, $this->tag, $this->source);

            if ($statusCode == 200) {
                info('MAWelcomeJob - Email Sent to customer '.$this->customer->email.' - Invite Code - '.$customerInviteCode);
                $customer = CustomerService::getCustomerByEmail($this->customer->email);
                if ($customer) {
                    // $customer->is_we_sent = true; // 13Sep2023 Shaji: need to uncomment after move from PostMark to Brevo
                    // $customer->save(); // 13Sep2023 Shaji: need to uncomment after move from PostMark to Brevo

                    $myAlfredUser = MyAlFredUser::where('customer_id', $customer->id)->get();
                    if ($myAlfredUser->isEmpty()) {
                        $newMyAlfredUser = new MyAlFredUser;
                        $newMyAlfredUser->signup_url = null;
                        $newMyAlfredUser->customer_id = $customer->id;
                        $newMyAlfredUser->code = $customerInviteCode;
                        $newMyAlfredUser->source = $this->source;
                        $newMyAlfredUser->save();
                    }
                }
            } else {
                info('MAWelcomeJob - Email not sent to customer '.$this->customer->email.' getStatusCode: '.$statusCode);
            }
        } catch (Exception $e) {
            Log::error('MAWelcomeJob - Error - Customer Email: '.$this->customer->email.' Message: '.$e->getMessage());
        }

        $sendSmsCustomerService->sendMAInviteSMS($this->customer, $customerInviteCode);
    }
}
