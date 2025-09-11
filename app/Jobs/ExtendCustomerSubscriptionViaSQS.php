<?php

namespace App\Jobs;

use App\Models\MyAlFredUser;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtendCustomerSubscriptionViaSQS implements ShouldQueue
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
        if (! $this->customer || ! $this->customer->email || ! isValidEmail($this->customer->email)) {
            LoggerService::info('ExtendCustomerSubscriptionViaSQS - Error - Empty/Invalid Customer email.');

            return false;
        }

        LoggerService::info('ExtendCustomerSubscriptionViaSQS - Job started - Customer ID: '.$this->customer->id);

        $this->extendCustomerSubscriptionViaSQS();
    }

    private function extendCustomerSubscriptionViaSQS()
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
        $customerDataArr['source'] = $this->source;
        $customerDataArr['tag'] = $this->tag;
        $customerDataJson = json_encode($customerDataArr);
        
        $sqsApiKey = config('constants.SQS_API_KEY');
        $sqsEndpoint = config('constants.SQS_API_ENDPOINT');
        
        $clientExtendSubscription = new \GuzzleHttp\Client;

        try {
            LoggerService::info('SQS Service - extendCustomerSubscriptionViaSQS - Start - Customer ID: '.$this->customer->id.' - Payload: ' . $customerDataJson);

            $requestExtendSubscription = $clientExtendSubscription->post(
                $sqsEndpoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'X-API-KEY' => $sqsApiKey,
                    ],
                    'body' => $customerDataJson,
                    'timeout' => 20,
                ]
            );

            $statusCode = $requestExtendSubscription->getStatusCode();
            
            LoggerService::info('SQS Service - extendCustomerSubscriptionViaSQS - Success - Customer ID: '.$this->customer->id.' Status Code: '.$statusCode);

            return true;
        } catch (\Exception $e) {
            LoggerService::error('SQS Service - extendCustomerSubscriptionViaSQS - Exception - Customer ID: '.$this->customer->id.' - Message: '.$e->getMessage());
            return false;
        }
    }
}