<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BirdService extends BaseService
{
    private $baseUrl = '';
    public function __construct()
    {
        $this->baseUrl = config('constants.BIRD_BASE_URL');
    }
    public function triggerWebHookRequest($url, $data, $method = 'post', $isAccessKey = false)
    {
        $uuid = $data->uuid ?? '';
        $logContext = ['Ref-ID' => $uuid, 'URL' => $url, 'Method' => $method];

        try {
            info('Bird Webhook Request initiated', $logContext);

            // Configure the HTTP request with headers
            $request = Http::withHeaders(['Content-Type' => 'application/json']);
            // Check if the AccessKey should be included
            if ($isAccessKey) {
                $birdAccessKey = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_ACCESS_KEY)->first();
                $request = $request->withHeaders(['Authorization' => 'AccessKey '.$birdAccessKey->value]);
            }

            // Dynamically call the HTTP method with the appropriate data
            $response = in_array(strtolower($method), ['post', 'put', 'patch'])
                ? $request->$method($url, $data)
                : $request->$method($url, ['query' => $data]);

            // Log the response details
            info("Bird Webhook Response: Ref-ID: {$uuid} | Status: {$response->status()} | Time: ".now());

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

    public function stopWorkFlow($workflow, $flowId = null)
    {
        $birdWorkSpaceId = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_WORKSPACE_ID)->first();
        $channelId = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_CHANNEL_ID)->first();
        $workflowId = $flowId ?? $channelId->value ?? null;
        if (! $birdWorkSpaceId || ! $workflowId) {
            info("Bird Workspace Id or Channel Id not found for lead : Ref-ID: {$workflow->quote_uuid} |Time: ".now());

            return false;
        }
        $cancelFlowRunUrl = "{$this->baseUrl}/workspaces/{$birdWorkSpaceId->value}/flows/{$workflowId}/runs";
        info('Bird Webhook Cancel Flow Run Request initiated', ['Ref-ID' => $workflow->quote_uuid, 'URL' => $cancelFlowRunUrl,  'run_id' => $workflow->flow_id, 'Method' => 'patch']);

        return $this->triggerWebHookRequest($cancelFlowRunUrl, ['action' => 'cancel', 'ids' => [$workflow->flow_id]], 'patch', true);
    }
}
