<?php

namespace App\Services;

use App\Models\ApplicationStorage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Enums\ApplicationStorageEnums;

class BirdService extends BaseService
{
    protected $birdEndpoint = 'https://api.bird.com';
    protected $birdaccessKey;
    protected $birdworkspaceId = "a1b37cbd-b29d-4371-a81a-c1cd939b73a2";
    protected $birdChannelId = "af77418d-fd91-4fd4-9263-24a04a8f54d6";
    public function __construct()
    {
        $this->birdEndpoint = config('constants.BIRD_API_ENDPOINT') ?? 'https://api.bird.com';
        $this->birdaccessKey = config('constants.BIRD_BASIC_AUTH_USER_NAME');
    }


    public function birdRequest($method ='get',$url, $data){

        try { // Configure the HTTP request with headers
                $request = Http::withHeaders([
                    'Authorization' => 'AccessKey ' . $this->birdaccessKey ?? null,
                    'Content-Type' => 'application/json',
                ]);
                // Dynamically call the HTTP method with the appropriate data
                if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
                    $response = $request->$method($this->birdEndpoint.$url, $data);
                } else {
                    $response = $request->$method($this->birdEndpoint.$url, ['query' => $data]);
                }
              // Return status code and response body
            return [
                'status_code' => $response->status(),
                'body' => $response->body() // or $response->body() for raw response
            ];
        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Bird API request failed', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            // return response()->json(['error' => 'API request failed'], 500);
            throw $e;
        }
    }
    public function birdWebHookRequest($method ='get',$url, $data){

        try {
             info('Bird Webhook URL : '.$url);
             info("Bird Webhook Data : ".json_encode($data));
             info("Starting Bird Webhook Request Ref-ID:" . $data->uuid ?? '');
            // Configure the HTTP request with headers
                $request = Http::withHeaders([
                    'Authorization' => 'AccessKey ' . $this->birdaccessKey ?? null,
                    'Content-Type' => 'application/json',
                ]);
                // Dynamically call the HTTP method with the appropriate data
                if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
                    $response = $request->$method($url, $data);
                } else {
                    $response = $request->$method($url, ['query' => $data]);
                }
              // Return status code and response body
                info('response code : '.$response->status());
                info('response body : '.$response->body() . '\n Ref-ID: ' . $data->uuid ?? '' . '\n');
            return $response->status();
        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Bird API request failed', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            // return response()->json(['error' => 'API request failed'], 500);
            throw $e;
        }
    }


    public function createContactIdentifier($data){
     return   $this->birdRequest('post', "/workspaces/{$data['workspaceId']}/contacts/{$data['uuid']}/identifiers", $data);
    }

    public function updateContactIdentifier($data){
        return   $this->birdRequest('put', "/workspaces/{$data['workspaceId']}/contacts/{$data['uuid']}/identifiers/{$data['identifierId']}", $data);
    }


    public function triggerWorkflow($webhook,$data){

        return  $this->birdWebHookRequest('post', $webhook, $data);
    }

    public function sendHealthOCBEmail($data){

        $webhook = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_OCB_FOLLOWUP_TEMPLATE)->first();
        if(!empty($webhook)){
            info('SIC Health OCB email template found REF:ID| '. $data->healthQuoteId.' Time: '.now());
            return $this->triggerWorkflow($webhook->value, $data);
        }
        else {
            info ('SIC Health OCB email template not found REF:ID| '. $data->healthQuoteId.' Time: '.now());
            return false;
        }
    }

    public function sendHealthNonAdvisorIntroEmail($data){
        $webhook = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_OCB_NON_ADVISOR_FOLLOWUP_TEMPLATE)->first();
        if(!empty($webhook)){
            info('SIC Health Non Advisor Intro Email email template found REF:ID | '. $data->healthQuoteId.' Time: '.now());
            return $this->triggerWorkflow($webhook->value, $data);
        }
        else {
            info ('SIC Health Non Advisor Intro Email email template not found REF:ID | '. $data->healthQuoteId.' Time: '.now());
            return false;
        }
    }

    public function sendSICHealthWorkFlow($data){
        $webhook = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
        if(!empty($webhook)){
            info('SIC Health OCB email template found REF:ID | '. $data->healthQuoteId.' Time: '.now());
            return $this->triggerWorkflow($webhook->value, $data);
        }
        else {
            info ('SIC Health OCB email template not found REF:ID | '. $data->healthQuoteId.' Time: '.now());
            return false;
        }
    }


    public function mapIdentifier($data){
        return
            [
                'identifierKey' => $data->key,
                'identifierValue' => $data->customerEmail,
                'type'=>$data->type,
            ];
    }
    public function mapPayloadForEmail($data){
       return
         [
            'receiver' => [
            'contacts' => [
                $this->mapIdentifier('customerEmail', $data->customerEmail, 'to'),
                $this->mapIdentifier('advisorEmail', $data->advisorEmail, 'cc'),
                $this->mapIdentifier('advisorEmail', $data->advisorEmail, 'bcc'),//end of identifier
                ], //end of contacts
            ],//end of receiver
            'template'=>[
                'projectId' => 'a1b37cbd-b29d-4371-a81a-c1cd939b73a2',
                'name' => 'default',
                'parameters' => $data->params,
            ]
        ];
    }
    public function sendEmail($data)
    {


        try {
            $response = $this->birdRequest('post', "/workspaces/{$data['workspaceId']}/channels/{$data['channelId']}/messages", $this->mapPayloadForEmail($data));

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error sending email via Bird API: ' . $e->getMessage());
            return null;
        }
    }



}
