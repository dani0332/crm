<?php

namespace App\Jobs;

use App\Models\MyAlFredUser;
use App\Services\CustomerService;
use App\Services\SendEmailCustomerService;
use App\Services\SendSmsCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MAWelcomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 20;
    private $first_name;
    private $last_name;
    private $email;
    private $mobile_no;
    private $source;
    private $tag;

    public function __construct($first_name, $last_name, $email, $mobile_no, $source, $tag)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->mobile_no = $mobile_no;
        $this->source = $source;
        $this->tag = $tag;
    }

    public function handle(SendEmailCustomerService $sendEmailCustomerService, SendSmsCustomerService $sendSmsCustomerService)
    {
        if (! $this->email) {
            info('MAWelcomeJob - Error - Empty Customer email.');

            return false;
        }

        $data = (object) [
            'customerFirstName' => $this->first_name,
            'customerLastName' => $this->last_name,
            'customerEmail' => $this->email,
        ];
        try {
            $statusCode = $sendEmailCustomerService->sendMyAlfredWelcomeEmail($data, $this->tag, $this->source);

            if ($statusCode == 201) {
                info('MAWelcomeJob - Email Sent to customer: '.$this->email);
                $customer = CustomerService::getCustomerByEmail($this->email);
                if ($customer) {
                    $customer->is_we_sent = true;
                    $customer->save();
                    DB::transaction(function () use ($customer) {
                        $myAlfredUser = MyAlFredUser::where('customer_id', $customer->id)->first();
                        if (! $myAlfredUser) {
                            try {
                                MyAlFredUser::create([
                                    'signup_url' => null,
                                    'customer_id' => $customer->id,
                                    'source' => $this->source,
                                ]);
                            } catch (Exception $e) {
                                info("MAWelcomeJob - MyAlFredUser customer {$customer->id} already created.");
                            }
                        }
                    }, 5);
                }
            } else {
                info('MAWelcomeJob - Email not sent to customer: '.$this->email.' getStatusCode: '.$statusCode);
            }
        } catch (Exception $e) {
            Log::error('MAWelcomeJob - Error - Customer Email: '.$this->email.' Message: '.$e->getMessage());
        }

        $sendSmsCustomerService->sendMAInviteSMS($this->email, $this->mobile_no);
    }
}
