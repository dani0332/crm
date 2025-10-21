<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\QuoteFlowDetails;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BirdService extends BaseService
{
    private $baseUrl = '';
    public function __construct()
    {
        $this->baseUrl = config('constants.BIRD_BASE_URL');
    }
    public function triggerWebHookRequest($url, $data, $method = 'post', $isAccessKey = false)
    {
   
        if(is_array($data)) {
            $uuid = $data['uuid'] ?? $data['refId'] ?? $data['quoteUID'] ?? '';
        } elseif (is_object($data)) {
            $uuid = $data->uuid ?? $data->refId ?? $data->quoteUID ?? '';
        } else {
            $uuid = '';
        }
        $logContext = ['Ref-ID' => $uuid, 'URL' => $url, 'Method' => $method];

        try {
            LoggerService::info('Bird Webhook Request initiated', $logContext);

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
            LoggerService::info("Bird Webhook Response: Ref-ID: {$uuid} | Status: {$response->status()} | Time: ".now());

            return (object) ['headers' => $response->headers() ?? '', 'body' => $response->body(), 'status_code' => $response->status()];
        } catch (\Exception $e) {
            // Log the error with full context and rethrow the exception
            LoggerService::error('Bird API request failed', array_merge($logContext, [
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
            LoggerService::warning("Bird Workspace Id or Channel Id not found for lead : Ref-ID: {$workflow->quote_uuid} |Time: ".now());

            return false;
        }
        $cancelFlowRunUrl = "{$this->baseUrl}/workspaces/{$birdWorkSpaceId->value}/flows/{$workflowId}/runs";
        LoggerService::info('Bird Webhook Cancel Flow Run Request initiated', ['Ref-ID' => $workflow->quote_uuid, 'URL' => $cancelFlowRunUrl,  'run_id' => $workflow->flow_id, 'Method' => 'patch']);

        return $this->triggerWebHookRequest($cancelFlowRunUrl, ['action' => 'cancel', 'ids' => [$workflow->flow_id]], 'patch', true);
    }

    public function isFollowupExecuted($quoteUuid, $quoteTypeId, $flowType)
    {
        return QuoteFlowDetails::where('quote_uuid', $quoteUuid)
            ->where('quote_type_id', $quoteTypeId)
            ->where('flow_type', $flowType)
            ->exists();

    }
    public function createQuoteWorkFlowDetails($lead, $response, $flowType = null, $quoteTypeId = null)
    {
        try {
            if (! empty($response->headers['Run-Id'])) {
                $runId = collect($response->headers['Run-Id'])->first();
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => $quoteTypeId,
                    'flow_type' => $flowType,
                    'flow_id' => $runId,
                    'started_at' => now(),
                ]);
                LoggerService::info('- createQuoteWorkFlowDetails  run id created');
            } else {
                LoggerService::info(' - createQuoteWorkFlowDetails  run id not found ');
            }
        } catch (\Throwable $th) {

            LoggerService::error(" - createQuoteWorkFlowDetails-Error: {$th->getMessage()} ");

        }
    }
    public function createQuoteWhatsAppFlowDetails($lead, $flowType = null, $quoteTypeId = null)
    {
        try {
            DB::table('ocb_whatsapp_msg_logs')->insert([
                'uuid' => $lead->uuid,
                'quote_type_id' => $quoteTypeId,
                'mobile_no' => formatMobileNoWithoutPlus($lead->mobile_no),
                'log_message' => Str::camel($flowType),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $th) {
            LoggerService::error(" - createQuoteWhatsAppFlowDetails-Error: {$th->getMessage()}  Line: {$th->getLine()}  Trace: {$th->getTraceAsString()} ");
        }
    }
}
