<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Models\MyAlFredUser;
use App\Services\CustomerService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Services\SendSmsCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MAWelcomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 45;
    public $backoff = 60;
    private $customer;
    private $source;
    private $tag;

    public function __construct($customer, $source, $tag)
    {
        $this->customer = $customer;
        $this->source = $source;
        $this->tag = $tag;
    }

    public function handle()
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::MA_WELCOME_JOB);
        LoggerService::info('MAWelcomeJob - Job started');
        if (! $this->customer || ! $this->customer->email || ! isValidEmail($this->customer->email)) {
            LoggerService::info('MAWelcomeJob - Error - Empty/Invalid Customer email.');

            return false;
        }

        $this->sendMAWelcomeEmail();
    }

    private function sendMAWelcomeEmail()
    {
        $data = (object) [
            'customerFirstName' => $this->customer->first_name,
            'customerLastName' => $this->customer->last_name,
            'customerEmail' => $this->customer->email,
        ];
        try {
            $statusCode = app(SendEmailCustomerService::class)->sendMyAlfredWelcomeEmail($data, $this->tag, $this->source);

            if ($statusCode == 201) {
                info('MAWelcomeJob - Email Sent to customer ID: '.$this->customer->id);
                $customer = CustomerService::getCustomerByEmail($this->customer->email);
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
                    if ($this->customer->mobile_no) {
                        app(SendSmsCustomerService::class)->sendMAInviteSMS($this->customer->email, $this->customer->mobile_no);
                    }
                }
            } else {
                info('MAWelcomeJob - Email not sent to customer ID: '.$this->customer->id.' getStatusCode: '.$statusCode);
            }
        } catch (Exception $e) {
            Log::error('MAWelcomeJob - Error - Customer ID: '.$this->customer->id.' Message: '.$e->getMessage());
        }
    }
}
