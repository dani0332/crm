<?php

namespace App\Jobs;

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
        try {
            Log::channel('daily')->info('Process SendInBlue Email trigged');
            $apiKey = Config::get('constants.SENDINBLUE_KEY');
            $url = Config::get('constants.SIB_URL');
            $sibTemplate = (int)Config::get('constants.SIB_CORPORATE_TEMPLATE');

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
            if ($getStatusCode == 201) {
                Log::channel('daily')->info('Email sent successfully - SIB');
                return;
            } else {
                throw new Error('SIB - Error dispatching to '.$this->email);
            }
        }
        catch(Exception $ex) {
            Log::channel('daily')->error('Error occured for SendInBlue Email to '. $this->email);
            Log::channel('daily')->error('Error SIB dispatch exception'.$ex);
            return $ex;
        }
    }
}
