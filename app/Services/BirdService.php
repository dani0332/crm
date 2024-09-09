<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BirdService extends BaseService
{

    private $birdWorkSpaceId = '';
    private $baseUrl = '';
    private $channelId = '';
    public function __construct(){
        $this->baseUrl = config('constants.BIRD_BASE_URL');
        $this->birdWorkSpaceId = config('constants.BIRD_WORKSPACE_ID');
        $this->channelId = config('constants.BIRD_CHANNEL_ID');
    }
    public function triggerWebHookRequest($url, $data, $method = 'post')
    {
        $uuid = $data->uuid ?? '';
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
            info('Bird Webhook Response received', array_merge($logContext, ['Header'=>$response->header(),'Status' => $response->status(), 'Body' => $response->body()]));

            return (object)['header'=>$response->header(),'body'=>$response->body(),'status_code'=>$response->status()];
        } catch (\Exception $e) {
            // Log the error with full context and rethrow the exception
            Log::error('Bird API request failed', array_merge($logContext, [
                'Data' => $data,
                'Error' => $e->getMessage(),
            ]));
            throw $e;
        }
    }

    public function stopWorkFlow($workflow){
        $cancelFlowRunUrl  = "{$this->baseUrl}/workspaces/{$this->birdWorkSpaceId}/flows/{$this->channelId}/runs";
        info ('Bird Webhook Cancel Flow Run Request initiated', ['Ref-ID' => $workflow->quote_uuid, 'URL' => $cancelFlowRunUrl, 'Method' => 'patch']);
        return $this->triggerWebHookRequest($cancelFlowRunUrl, ['action' => 'cancel','ids'=>[$workflow->flow_id]],'patch');
    }
}
