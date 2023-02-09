<?php

namespace App\Jobs;

use App\Models\MyAlFredUser;
use App\Services\BerlinService;
use App\Services\CustomerService;
use App\Services\SendEmailCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MAWelcomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $email;
    private $firstName;
    private $lastName;
    private $source;
    private $tag;

    public function __construct($email, $firstName, $lastName, $source, $tag)
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->source = $source;
        $this->tag = $tag;
    }

    public function handle(BerlinService $berlinService, SendEmailCustomerService $sendEmailCustomerService)
    {
        $customerInviteCode = $berlinService->getCustomerInviteCode();

        try {
            $emailData = (object) [
                'customerFirstName' => $this->firstName,
                'customerLastName' => $this->lastName,
                'customerEmail' => $this->email,
                'inviteCode' => $customerInviteCode,
            ];

            $statusCode = $sendEmailCustomerService->sendMyAlfredWelcomeEmail($emailData, $this->tag, $this->source);

            if ($statusCode == 200) {
                info('MAWelcomeEmail Job - Email Sent to customer '.$this->email);
                $customer = CustomerService::getCustomerByEmail($this->email);
                if ($customer) {
                    $customer->is_we_sent = true;
                    $customer->save();

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
                info('MAWelcomeEmail Job - Email not sent to customer '.$this->email.' getStatusCode: '.$statusCode);
            }
        } catch (Exception $e) {
            Log::error('MAWelcomeEmail Job - Error - Customer Email: '.$this->email.' Message: '.$e->getMessage());

            return $e->getMessage();
        }
    }
}
