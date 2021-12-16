<?php

namespace App\Jobs;

use Error;
use Exception;
use Faker\Core\Number;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        try {
            Log::channel('daily')->info('Process SendInBlue Email trigged');
            $apiKey = env('SENDINBLUE_KEY');
            $url = env('SIB_URL');
            $sibTemplate = (int)env('SIB_CORPORATE_TEMPLATE');

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ];

            $body = json_encode([
                "to" => array([
                    "email" => $this->email,
                    "name" => $this->name,
                ]),
                "templateId" => $sibTemplate,
            ]);

            Log::channel('daily')->info('sending email via http BODY - SIB '.$body);

            $client = new \GuzzleHttp\Client();
            $capiRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $getStatusCode = $capiRequest->getStatusCode();
            
            Log::channel('daily')->info('sending email via http - SIB '.$getStatusCode);

        }
        catch(Exception $ex) {
            Log::channel('daily')->error('Error occured for SendInBlue Email to '. $this->email);
            Log::channel('daily')->error('Error SIB dispatch exception'.$ex);
            return $ex;
        }
    }
}
