<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BirdService extends BaseService
{

    public function triggerWebHookRequest($url, $data, $method ='post')
    {

        try {
            info('Bird Webhook URL : '.$url. '| Ref-ID:'.$data->uuid ?? '');
            info('Starting Bird Webhook Request Ref-ID:'.$data->uuid ?? '');
            // Configure the HTTP request with headers
            $request = Http::withHeaders([
                'Content-Type' => 'application/json',
            ]);
            // Dynamically call the HTTP method with the appropriate data
            if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
                $response = $request->$method($url, $data);
            } else {
                $response = $request->$method($url, ['query' => $data]);
            }
            // Return status code and response body
            info('response code : '.$response->status().'\n Ref-ID: '.$data->uuid ?? ''.'\n');
            info('response body : '.$response->body().'\n Ref-ID: '.$data->uuid ?? ''.'\n');
            return $response->status();
        } catch (\Exception $e) {
            // Log the error with details
            $refid ='Ref-ID: '.$data->uuid ?? '';
            Log::error($refid.'Bird API request failed', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

}
