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
    public $timeout = 45;
    public $backoff = 60;
    private $customer;
    private $source;
    private $tag;
    private $berlinEndpoint;
    private $berlinUserName;
    private $berlinAuthPassword;

    public function __construct($customer, $source, $tag)
    {
        $this->customer = $customer;
        $this->source = $source;
        $this->tag = $tag;

        $this->berlinEndpoint = config('constants.BERLIN_API_ENDPOINT');
        $this->berlinUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $this->berlinAuthPassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');
    }

    public function handle()
    {
        if (! $this->customer || ! $this->customer->email || ! isValidEmail($this->customer->email)) {
            info('MAWelcomeJob - Error - Empty/Invalid Customer email.');

            return false;
        }
        if (! $this->extendCustomerSubscription()) {
            $this->sendMAWelcomeEmail();
        }
    }

    private function extendCustomerSubscription()
    {
        $customer = MyAlFredUser::select('signup_url', 'code')->where('customer_id', $this->customer->id)->latest()->first();

        if (! $customer) {
            $customer = $this->customer;
            $customer->code = null;
        }

        $isToken = strlen($customer->code) > 8;
        $hasToken = ! is_null($customer->code);

        $customerDataArr = [];

        if ($hasToken) {
            $customerDataArr[$isToken ? 'token' : 'otp'] = $customer->code;
        }

        $customerDataArr['email'] = $this->customer->email;
        $customerDataJson = json_encode($customerDataArr);
        $magicUrlGeneratauthBasic = base64_encode($this->berlinUserName.':'.$this->berlinAuthPassword);
        $clientExtendSubscription = new \GuzzleHttp\Client;

        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $this->berlinEndpoint.'/internal/extend-subscription',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$magicUrlGeneratauthBasic,
                    ],
                    'body' => $customerDataJson,
                    'timeout' => 20,
                ]
            );

            $statusCode = $requestExtendSubscription->getStatusCode();

            return true;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $statusCode = $e->getResponse()->getStatusCode();

            $errorData = json_decode($e->getResponse()->getBody()->getContents(), true);

            if (isset($errorData['code']) && $errorData['code'] == 'CUSTOMER_NOT_FOUND') {
                return false;
            } else {
                Log::error('Berlin Service - extendCustomerSubscription - Fail - Customer ID: '.$customer->id.' Status Code: '.$statusCode.' - Message: '.$e->getMessage());
            }

            return false;
        }
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
                info('MAWelcomeJob - Email Sent to customer: '.$this->customer->email);
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
                }
            } else {
                info('MAWelcomeJob - Email not sent to customer: '.$this->customer->email.' getStatusCode: '.$statusCode);
            }
        } catch (Exception $e) {
            Log::error('MAWelcomeJob - Error - Customer Email: '.$this->customer->email.' Message: '.$e->getMessage());
        }
        if ($this->customer->mobile_no) {
            app(SendSmsCustomerService::class)->sendMAInviteSMS($this->customer->email, $this->customer->mobile_no);
        }
    }
}
