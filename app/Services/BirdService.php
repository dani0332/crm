<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;

class BirdService extends BaseService
{
    public function triggerWebHookRequest($url, $data, $method = 'post')
    {
        $uuid = $data->uuid ?? $data->quoteUUID ?? '';
        $logContext = ['Ref-ID' => $uuid, 'URL' => $url, 'Method' => $method];

        try {
            info('Bird Webhook Request initiated', $logContext);

            // Configure the HTTP request with headers
            $request = Http::withHeaders(['Content-Type' => 'application/json']);

            // Dynamically call the HTTP method with the appropriate data
            $response = in_array(strtolower($method), ['post', 'put', 'patch'])
                ? $request->$method($url, $data)
                : $request->$method($url, ['query' => $data]);

            // Log the response details
            info('Bird Webhook Response received', array_merge($logContext, ['Headers' => $response->headers() ?? '', 'Status' => $response->status(), 'Body' => $response->body()]));

            return (object) ['headers' => $response->headers() ?? '', 'body' => $response->body(), 'status_code' => $response->status()];
        } catch (\Exception $e) {
            // Log the error with full context and rethrow the exception
            Log::error('Bird API request failed', array_merge($logContext, [
                'Data' => $data,
                'Error' => $e->getMessage(),
            ]));
            throw $e;
        }
    }



   public function stopWorkFlow($workflow)
   {
       $birdWorkSpaceId = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_WORKSPACE_ID)->first();
       $channelId = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_CHANNEL_ID)->first();
       if (! $birdWorkSpaceId || ! $channelId) {
           info("Bird Workspace Id or Channel Id not found for lead : Ref-ID: {$workflow->quote_uuid} |Time: ".now());

           return false;
       }
       $cancelFlowRunUrl = "{$this->baseUrl}/workspaces/{$birdWorkSpaceId->value}/flows/{$channelId->value}/runs";
       info('Bird Webhook Cancel Flow Run Request initiated', ['Ref-ID' => $workflow->quote_uuid, 'URL' => $cancelFlowRunUrl, 'Method' => 'patch']);

       return $this->triggerWebHookRequest($cancelFlowRunUrl, ['action' => 'cancel', 'ids' => [$workflow->flow_id]], 'patch');
   }

}
