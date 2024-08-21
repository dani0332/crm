<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BirdService extends BaseService
{
    protected $birdEndpoint;
    protected $birdaccessKey;
    public function __construct()
    {
        $this->birdEndpoint = config('constants.BIRD_API_ENDPOINT');
        $this->birdaccessKey = config('constants.BIRD_BASIC_AUTH_USER_NAME');
    }

    public function birdRequest($method, $url, $data)
    {

        try { // Configure the HTTP request with headers
            $request = Http::withHeaders([
                'Authorization' => 'AccessKey '.$this->birdaccessKey ?? null,
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
                'body' => $response->body(), // or $response->body() for raw response
            ];
        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Bird API request failed', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    public function birdWebHookRequest($method, $url, $data)
    {

        try {
            // Configure the HTTP request with headers
            $request = Http::withHeaders([
                'Authorization' => 'AccessKey '.$this->birdaccessKey ?? null,
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
            info('response body : '.$response->body().'\n Ref-ID: '.$data->uuid ?? ''.'\n');

            return $response->status();
        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Bird API request failed', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function createContactIdentifier($data)
    {
        return $this->birdRequest('post', "/workspaces/{$data['workspaceId']}/contacts/{$data['uuid']}/identifiers", $data);
    }

    public function updateContactIdentifier($data)
    {
        return $this->birdRequest('put', "/workspaces/{$data['workspaceId']}/contacts/{$data['uuid']}/identifiers/{$data['identifierId']}", $data);
    }

    public function triggerWorkflow($webhook, $data)
    {

        return $this->birdWebHookRequest('post', $webhook, $data);
    }

    public function sendOCAHealthWorkFlow($data)
    {
        $webhook = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_OCA_HEALTH_WORKFLOW)->first();
        if (! empty($webhook)) {
            info('Health OCA email template found REF:ID | '.$data->healthQuoteId.'| Time: '.now());

            return $this->triggerWorkflow($webhook->value, $data);
        } else {
            info('Health OCA email template not found REF:ID | '.$data->healthQuoteId.'| Time: '.now());

            return false;
        }
    }

    public function sendAppPendingHealthWorkFlow($data)
    {
        $webhook = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_APP_PENDING_HEALTH_WORKFLOW)->first();
        if (! empty($webhook)) {
            info('Health App Pending email template found REF:ID | '.$data->healthQuoteId.' Time: '.now());

            return $this->triggerWorkflow($webhook->value, $data);
        } else {
            info('Health App Pending emaill template not found REF:ID | '.$data->healthQuoteId.' Time: '.now());

            return false;
        }
    }

}
