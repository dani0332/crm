<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Models\ApplicationStorage;
use App\Models\QuoteFlowDetails;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BirdWorkflowAdminService extends BaseService
{
    public function __construct(private readonly BirdService $birdService) {}

    /**
     * @return array{workspace_id: string, flow_id: string}|null
     */
    public function parseWorkflowUrl(string $url): ?array
    {
        return $this->birdService->parseWorkflowUrl($url);
    }

    /**
     * Map a QuoteFlowType to its corresponding ApplicationStorage key for the trigger URL.
     *
     * @return string|null ApplicationStorageEnums constant value or null if unmapped
     */
    public function resolveWorkflowUrlKey(QuoteFlowType $flowType): ?string
    {
        return match ($flowType) {
            QuoteFlowType::HEALTH_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::HEALTH_SIC_FOLLOWUPS,
            QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA => ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW,
            QuoteFlowType::NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::MOTOR_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS => ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW,
            QuoteFlowType::MOTOR_SIC_FOLLOWUPS => ApplicationStorageEnums::BIRD_SIC_MOTOR_RENEWAL_WORKFLOW,
            QuoteFlowType::MOTOR_PCP_FOLLOWUPS => ApplicationStorageEnums::MOTOR_PCP_FOLLOWUPS,
            QuoteFlowType::TRAVEL_SIC_FOLLOWUPS,
            QuoteFlowType::TRAVEL_AUTOMATED_FOLLOWUPS => ApplicationStorageEnums::BIRD_TRAVEL_FLLOWUP_DEDICATED_WORKFLOW_URL,
            QuoteFlowType::TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS => ApplicationStorageEnums::BIRD_TRAVEL_RENEWALS_OCB,
            QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS => ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS => ApplicationStorageEnums::HOME_RENEWAL_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::CAR_AUTOMATION_FAILED,
            QuoteFlowType::CYBER_AUTOMATION_FAILED => ApplicationStorageEnums::BIRD_AUTOMATION_WORKFLOW_URL,
            QuoteFlowType::CAR_MISSING_DOC_REMINDER => ApplicationStorageEnums::BIRD_CAR_MISSING_DOC_REMINDER_WORKFLOW,
            QuoteFlowType::CAR_AI_ADVISOR_OCB => ApplicationStorageEnums::BIRD_AI_ADVISOR_OCB,
            QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS => ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::CYBER_OCB_INTRO_EMAIL => ApplicationStorageEnums::BIRD_CYBER_OCB_INTRO_EMAIL,
            QuoteFlowType::LIFE_AUTOMATED_FOLLOWUPS,
            QuoteFlowType::LIFE_ADVANCE_BIRTHDAY_WISH,
            QuoteFlowType::LIFE_BIRTHDAY_WISH => ApplicationStorageEnums::FIC_LIFE_EMAIL,
            QuoteFlowType::SAVINGS_OCA_EMAIL => ApplicationStorageEnums::SAVINGS_OCA_EMAIL_FLOW,
            QuoteFlowType::HEALTH_STP_ADVISOR_NOTIFICATION,
            QuoteFlowType::HEALTH_STP_ADVISOR_NOTIFICATION_API_FAILED => ApplicationStorageEnums::BIRD_HEALTH_STP_ADVISOR_NOTIFICATION_WORKFLOW,
            default => null,
        };
    }

    /**
     * Retrigger a Bird workflow run for the given QuoteFlowDetails entry.
     *
     * @return string|null The new run ID if returned by Bird, or null
     *
     * @throws \RuntimeException if the workflow URL cannot be resolved
     */
    public function retrigger(QuoteFlowDetails $quoteFlowDetails): ?string
    {
        $rawFlowType = (int) $quoteFlowDetails->getRawOriginal('flow_type');

        return $this->retriggerByQuoteAndFlowType(
            $quoteFlowDetails->quote_uuid,
            (int) $quoteFlowDetails->quote_type_id,
            $rawFlowType,
            $quoteFlowDetails->flow_id,
        );
    }

    /**
     * Retrigger using quote + flow type (same behaviour as {@see retrigger()} without a model).
     *
     * @return string|null The new run ID if returned by Bird, or null
     *
     * @throws \RuntimeException if the workflow URL cannot be resolved
     */
    public function retriggerByQuoteAndFlowType(
        string $quoteUuid,
        int $quoteTypeId,
        int $flowTypeValue,
        ?string $originalFlowIdForLog = null,
    ): ?string {
        $flowType = QuoteFlowType::tryFrom($flowTypeValue);

        if (! $flowType) {
            throw new \RuntimeException("Unknown flow type value: {$flowTypeValue}");
        }

        $urlKey = $this->resolveWorkflowUrlKey($flowType);

        if (! $urlKey) {
            throw new \RuntimeException("No retrigger URL mapped for flow type: {$flowType->name}");
        }

        $workflowStorage = ApplicationStorage::where('key_name', $urlKey)->first();

        if (! $workflowStorage || empty($workflowStorage->value)) {
            throw new \RuntimeException("Workflow URL not configured for key: {$urlKey}");
        }

        $payload = [
            'quoteUID' => $quoteUuid,
            'uuid' => $quoteUuid,
            'refId' => $quoteUuid,
            'admin_retrigger' => true,
            'retrigger_source' => 'admin_panel',
        ];

        LoggerService::info('BirdWorkflowAdminService: Retriggering workflow', [
            'quote_uuid' => $quoteUuid,
            'flow_type' => $flowType->name,
            'url_key' => $urlKey,
            'original_flow_id' => $originalFlowIdForLog,
        ]);

        $response = $this->birdService->triggerWebHookRequest($workflowStorage->value, $payload, 'post', true);

        $newRunId = null;
        if (isset($response->headers['Run-Id'])) {
            $newRunId = is_array($response->headers['Run-Id'])
                ? collect($response->headers['Run-Id'])->first()
                : $response->headers['Run-Id'];
        } elseif (isset($response->headers['run-id'])) {
            $newRunId = is_array($response->headers['run-id'])
                ? collect($response->headers['run-id'])->first()
                : $response->headers['run-id'];
        }

        if ($newRunId) {
            QuoteFlowDetails::create([
                'quote_uuid' => $quoteUuid,
                'quote_type_id' => $quoteTypeId,
                'flow_type' => $flowTypeValue,
                'flow_id' => $newRunId,
                'started_at' => now(),
            ]);
        }

        LoggerService::info('BirdWorkflowAdminService: Retrigger completed', [
            'quote_uuid' => $quoteUuid,
            'new_run_id' => $newRunId,
        ]);

        return $newRunId;
    }

    /**
     * Best-effort quote / ref identifier from a Bird trigger body.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function extractQuoteUuidFromBirdPayload(?array $payload): ?string
    {
        if ($payload === null || $payload === []) {
            return null;
        }

        foreach (['quoteUID', 'quoteUid', 'quote_uuid', 'refID', 'refId', 'ref_id', 'uuid'] as $key) {
            if (! empty($payload[$key]) && is_string($payload[$key])) {
                return $payload[$key];
            }
        }

        return null;
    }

    /**
     * Resolve workspace ID, Bird flow definition ID, and workflow URL for a flow type value.
     *
     * @return array{workspace_id: string, flow_id: string, workflow_url: string}|null
     */
    public function resolveWorkspaceAndFlowForFlowTypeValue(int $flowTypeValue): ?array
    {
        $flowType = QuoteFlowType::tryFrom($flowTypeValue);

        if (! $flowType) {
            return null;
        }

        $urlKey = $this->resolveWorkflowUrlKey($flowType);

        if (! $urlKey) {
            return null;
        }

        $workflowStorage = ApplicationStorage::where('key_name', $urlKey)->first();

        if (! $workflowStorage || empty($workflowStorage->value)) {
            return null;
        }

        $parsed = $this->birdService->parseWorkflowUrl($workflowStorage->value);

        if (! $parsed) {
            return null;
        }

        return [
            'workspace_id' => $parsed['workspace_id'],
            'flow_id' => $parsed['flow_id'],
            'workflow_url' => $workflowStorage->value,
        ];
    }

    /**
     * Resolve workspace ID, Bird flow definition ID, and workflow URL for a local quote_flow_details row.
     *
     * @return array{workspace_id: string, flow_id: string, workflow_url: string}|null
     */
    public function resolveWorkspaceAndFlowForQuoteFlow(QuoteFlowDetails $quoteFlowDetails): ?array
    {
        return $this->resolveWorkspaceAndFlowForFlowTypeValue(
            (int) $quoteFlowDetails->getRawOriginal('flow_type'),
        );
    }

    /**
     * Cancel an active Bird workflow run.
     *
     * Resolves the Bird flow definition ID from ApplicationStorage when not provided.
     *
     * @throws \RuntimeException if the run or workflow URL cannot be resolved
     */
    public function cancelRun(QuoteFlowDetails $quoteFlowDetails, ?string $birdWorkflowId = null): mixed
    {
        if (empty($quoteFlowDetails->flow_id)) {
            throw new \RuntimeException('Cannot cancel: no run ID on record (workflow may not have been triggered successfully).');
        }

        $resolved = $this->resolveWorkspaceAndFlowForQuoteFlow($quoteFlowDetails);

        if (! $resolved) {
            throw new \RuntimeException('Cannot cancel: workflow URL could not be resolved for this flow type.');
        }

        $definitionId = ($birdWorkflowId !== null && $birdWorkflowId !== '')
            ? $birdWorkflowId
            : $resolved['flow_id'];

        return $this->birdService->stopWorkFlow(
            $quoteFlowDetails,
            $definitionId,
            $resolved['workspace_id'],
        );
    }

    /**
     * Cancel multiple Bird runs, grouped by workspace + flow definition (one PATCH per group).
     *
     * @param  list<array{workspace_id: string, flow_id: string, run_id: string}>  $runs
     * @return list<array{group_key: string, run_ids: list<string>, success: bool, message?: string}>
     */
    public function cancelBirdApiRunsGrouped(array $runs): array
    {
        /** @var array<string, array{workspace_id: string, flow_id: string, run_ids: array<string, true>}> $groups */
        $groups = [];

        foreach ($runs as $run) {
            $ws = trim((string) ($run['workspace_id'] ?? ''));
            $fd = trim((string) ($run['flow_id'] ?? ''));
            $rid = trim((string) ($run['run_id'] ?? ''));

            if ($ws === '' || $fd === '' || $rid === '') {
                continue;
            }

            $key = $ws.'|'.$fd;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'workspace_id' => $ws,
                    'flow_id' => $fd,
                    'run_ids' => [],
                ];
            }

            $groups[$key]['run_ids'][$rid] = true;
        }

        $results = [];

        foreach ($groups as $key => $group) {
            $runIds = array_keys($group['run_ids']);

            try {
                $this->birdService->cancelFlowRuns(
                    $group['workspace_id'],
                    $group['flow_id'],
                    $runIds,
                );

                $results[] = [
                    'group_key' => $key,
                    'run_ids' => $runIds,
                    'success' => true,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'group_key' => $key,
                    'run_ids' => $runIds,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * All ApplicationStorage key names that hold Bird invoke-sync workflow URLs.
     *
     * @return list<string>
     */
    public function workflowUrlKeys(): array
    {
        return [
            ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW,
            ApplicationStorageEnums::BIRD_SIC_MOTOR_RENEWAL_WORKFLOW,
            ApplicationStorageEnums::BIRD_TRAVEL_FLLOWUP_DEDICATED_WORKFLOW_URL,
            ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW,
            ApplicationStorageEnums::BIRD_TRAVEL_RENEWALS_OCB,
            ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS,
            ApplicationStorageEnums::HOME_RENEWAL_AUTOMATED_FOLLOWUPS,
            ApplicationStorageEnums::MOTOR_PCP_FOLLOWUPS,
            ApplicationStorageEnums::FIC_LIFE_EMAIL,
            ApplicationStorageEnums::SAVINGS_OCA_EMAIL_FLOW,
            ApplicationStorageEnums::BIRD_WHATSAPP_NO_PLANS_ASSIGNMENT_WORKFLOW,
            ApplicationStorageEnums::BIRD_INSLY_WORKFLOW,
            ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW,
            ApplicationStorageEnums::BIRD_AIG_WORKFLOW,
            ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL,
            ApplicationStorageEnums::BIRD_AI_ADVISOR_OCB,
            ApplicationStorageEnums::BIRD_AUTOMATION_WORKFLOW_URL,
            ApplicationStorageEnums::BIRD_OE_ASSIGNMENT_WORKFLOW,
            ApplicationStorageEnums::BIRD_CAR_MISSING_DOC_REMINDER_WORKFLOW,
            ApplicationStorageEnums::BIRD_EP_WORKFLOW_URL,
            ApplicationStorageEnums::BIRD_CYBER_OCB_INTRO_EMAIL,
            ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS,
            ApplicationStorageEnums::BIRD_HEALTH_STP_ADVISOR_NOTIFICATION_WORKFLOW,
            ApplicationStorageEnums::BIRD_MISREPORT_JOB_WORKFLOW,
            ApplicationStorageEnums::BIRD_SEND_FAILED_ILA_EMAILS_WORKFLOW,
            ApplicationStorageEnums::BIRD_MANAGER_DEACTIVATION_ATTEMPT_WORKFLOW,
            ApplicationStorageEnums::BIRD_ADVISOR_PAYMENT_NOTIFICATION_WORKFLOW_URL,
            ApplicationStorageEnums::BIRD_INSTANT_ALFRED_EXPORT_WORKFLOW,
        ];
    }

    /**
     * Return workflow keys enriched with their configured URL from ApplicationStorage,
     * suitable for the admin frontend filter UI.
     *
     * @return array<int, array{key: string, label: string, url: string|null, configured: bool}>
     */
    public function getWorkflowKeysForFrontend(): array
    {
        $keys = $this->workflowUrlKeys();

        $stored = ApplicationStorage::whereIn('key_name', $keys)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->pluck('value', 'key_name');

        return collect($keys)->map(function (string $key) use ($stored) {
            $url = $stored[$key] ?? null;
            $parsed = $url ? $this->birdService->parseWorkflowUrl($url) : null;

            return [
                'key' => $key,
                'label' => $this->formatKeyLabel($key),
                'url' => $url,
                'flow_id' => $parsed['flow_id'] ?? null,
                'configured' => $url !== null,
            ];
        })->values()->all();
    }

    /**
     * Fetch full detail for a single Bird run and return it enriched with resolved payload fields.
     * The Bird detail endpoint returns a `trigger` field containing the original invocation body.
     *
     * @return array<string, mixed>
     */
    public function enrichRunWithDetail(string $workspaceId, string $flowId, string $runId): array
    {
        $detail = $this->birdService->getFlowRunDetail($workspaceId, $flowId, $runId);

        if (! $detail) {
            return [
                'error' => 'Could not fetch run detail from Bird API',
                'run_id' => $runId,
            ];
        }

        // Log the full trigger JSON so we can see exactly what keys are present
        $trigger = $detail['trigger'] ?? null;
        LoggerService::info('BirdWorkflowAdminService::enrichRunWithDetail — trigger structure', [
            'run_id' => $runId,
            'trigger_type' => gettype($trigger),
            'trigger_keys' => is_array($trigger) ? array_keys($trigger) : null,
            'trigger_body_type' => gettype($trigger['body'] ?? null),
            'trigger_body_keys' => is_array($trigger['body'] ?? null) ? array_keys($trigger['body']) : null,
            'trigger_json_preview' => is_array($trigger)
                ? mb_substr(json_encode($trigger), 0, 800)
                : null,
        ]);

        $body = $this->resolveRunBody($detail);

        LoggerService::info('BirdWorkflowAdminService::enrichRunWithDetail — resolved body', [
            'run_id' => $runId,
            'body_keys' => array_keys($body),
            'ref_id' => $body['refID'] ?? $body['refId'] ?? null,
            'quote_uid' => $body['quoteUID'] ?? null,
            'workflow_type' => $body['workflowType'] ?? null,
        ]);

        // If resolveRunBody found nothing, fall back to the raw detail (minus the huge steps array)
        if (empty($body)) {
            $body = array_filter(
                $detail,
                fn ($v, $k) => ! in_array($k, ['steps', 'output'], true),
                ARRAY_FILTER_USE_BOTH
            );
        }

        return [
            'run_id' => $runId,
            'flow_id' => $flowId,
            'workspace_id' => $workspaceId,
            'status' => $detail['status'] ?? null,
            'error' => $this->resolveRunError($detail),
            'created_at' => $detail['createdAt'] ?? $detail['created_at'] ?? null,
            'finished_at' => $detail['finishedAt'] ?? $detail['finished_at'] ?? $detail['endedAt'] ?? null,
            'ref_id' => $body['refID'] ?? $body['refId'] ?? $body['ref_id'] ?? null,
            'quote_uid' => $body['quoteUID'] ?? $body['quoteUid'] ?? $body['quote_uid'] ?? null,
            'workflow_type' => $body['workflowType'] ?? $body['workflow_type'] ?? null,
            'contact_id' => $body['contactId'] ?? $body['contact_id'] ?? null,
            'original_payload' => $body,
            'raw' => $detail,
        ];
    }

    /**
     * Resolve the original trigger body/payload from a Bird run detail object.
     *
     * Bird's detail endpoint stores the original invocation under `trigger`.
     * The trigger node can be:
     *   - trigger.body        (HTTP-trigger, most common for invoke-sync)
     *   - trigger.properties  (some event triggers)
     *   - trigger             (flat object if no sub-key)
     * Also handles 'body', 'input', 'properties', 'context' at the top level as fallbacks.
     *
     * @param  array<string, mixed>  $run
     * @return array<string, mixed>
     */
    private function resolveRunBody(array $run): array
    {
        // Bird http_endpoint trigger: trigger.payload.body  ← CONFIRMED structure
        if (! empty($run['trigger']['payload']['body']) && is_array($run['trigger']['payload']['body'])) {
            return $run['trigger']['payload']['body'];
        }

        // Bird http_endpoint trigger: trigger.payload as body directly (non-nested)
        if (! empty($run['trigger']['payload']) && is_array($run['trigger']['payload'])) {
            $payload = $run['trigger']['payload'];
            // Only use payload directly if it looks like application data (not an HTTP envelope)
            $httpEnvelopeKeys = ['body', 'headers', 'method', 'querystring', 'contentType'];
            $payloadKeys = array_diff(array_keys($payload), $httpEnvelopeKeys);
            if (! empty($payloadKeys)) {
                return $payload;
            }
        }

        // Direct trigger.body
        if (! empty($run['trigger']['body']) && is_array($run['trigger']['body'])) {
            return $run['trigger']['body'];
        }

        // trigger.data
        if (! empty($run['trigger']['data']) && is_array($run['trigger']['data'])) {
            return $run['trigger']['data'];
        }

        // trigger.request.body
        if (! empty($run['trigger']['request']['body']) && is_array($run['trigger']['request']['body'])) {
            return $run['trigger']['request']['body'];
        }

        // trigger.properties
        if (! empty($run['trigger']['properties']) && is_array($run['trigger']['properties'])) {
            return $run['trigger']['properties'];
        }

        // trigger itself contains application keys directly
        if (! empty($run['trigger']) && is_array($run['trigger'])) {
            $trigger = $run['trigger'];
            if (isset($trigger['refID']) || isset($trigger['quoteUID']) || isset($trigger['workflowType'])) {
                return $trigger;
            }

            // Return trigger as last resort (always return something)
            return $trigger;
        }

        // Top-level fallbacks
        if (! empty($run['body']) && is_array($run['body'])) {
            return $run['body'];
        }

        if (! empty($run['input']) && is_array($run['input'])) {
            return $run['input'];
        }

        return [];
    }

    /**
     * Resolve the error/failure message from a Bird run object.
     *
     * @param  array<string, mixed>  $run
     */
    private function resolveRunError(array $run): ?string
    {
        return $run['error'] ?? $run['errorMessage'] ?? $run['error_message']
            ?? $run['failure_reason'] ?? $run['failureReason']
            ?? (isset($run['error']['message']) ? $run['error']['message'] : null)
            ?? null;
    }

    /**
     * Convert an ApplicationStorage key name into a human-readable label.
     */
    private function formatKeyLabel(string $key): string
    {
        return str_replace('_', ' ', preg_replace('/^BIRD_/', '', $key) ?? $key);
    }

    /**
     * Fetch failed/error workflow runs from the Bird API across all (or selected) workflows.
     * All flows are queried in PARALLEL using Http::pool() to avoid sequential timeout accumulation.
     *
     * @param  list<string>  $statuses  Bird run status filters e.g. ['error', 'failed']
     * @param  int  $limitPerFlow  Max runs to fetch per individual flow per status
     * @param  list<string>|null  $filterKeys  If provided, only query these ApplicationStorage key names
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchFailedRunsFromBirdApi(array $statuses = ['error', 'failed'], int $limitPerFlow = 50, ?array $filterKeys = null): Collection
    {
        $keys = $filterKeys ? array_intersect($this->workflowUrlKeys(), $filterKeys) : $this->workflowUrlKeys();

        $storageRows = ApplicationStorage::whereIn('key_name', $keys)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get()
            ->keyBy('key_name');

        $birdAccessKey = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_ACCESS_KEY)->first();
        $accessKey = $birdAccessKey?->value;

        if (! $accessKey) {
            return collect();
        }

        // Build a flat list of tasks: each task is one (flowId, status) pair
        /** @var array<string, array{key_name: string, flow_id: string, workspace_id: string, workflow_url: string, status: string}> $tasks */
        $tasks = [];
        $seenFlowIds = [];

        foreach ($storageRows as $keyName => $storage) {
            $parsed = $this->birdService->parseWorkflowUrl($storage->value);

            if (! $parsed) {
                continue;
            }

            $flowId = $parsed['flow_id'];

            if (isset($seenFlowIds[$flowId])) {
                continue;
            }
            $seenFlowIds[$flowId] = true;

            foreach ($statuses as $status) {
                $taskKey = $flowId.'__'.$status;
                $tasks[$taskKey] = [
                    'key_name' => $keyName,
                    'flow_id' => $flowId,
                    'workspace_id' => $parsed['workspace_id'],
                    'workflow_url' => $storage->value,
                    'status' => $status,
                ];
            }
        }

        if (empty($tasks)) {
            return collect();
        }

        $baseUrl = config('constants.BIRD_BASE_URL');
        $taskKeys = array_keys($tasks);

        // Fire all requests concurrently using void-callback style so ->as() names are preserved
        $responses = Http::pool(function (Pool $pool) use ($tasks, $taskKeys, $baseUrl, $accessKey, $limitPerFlow): void {
            foreach ($taskKeys as $taskKey) {
                $t = $tasks[$taskKey];
                $url = "{$baseUrl}/workspaces/{$t['workspace_id']}/flows/{$t['flow_id']}/runs";
                $pool->as($taskKey)
                    ->timeout(12)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'AccessKey '.$accessKey,
                    ])
                    ->get($url, ['status' => $t['status'], 'limit' => $limitPerFlow]);
            }
        });

        $aggregated = collect();
        $seenRunIds = [];

        foreach ($taskKeys as $taskKey) {
            $task = $tasks[$taskKey];
            $response = $responses[$taskKey] ?? null;

            // Skip missing, exceptions (e.g. ConnectionException on 504), or non-2xx responses
            if (! $response || $response instanceof \Throwable || ! $response->successful()) {
                continue;
            }

            $json = $response->json();
            if (! is_array($json)) {
                continue;
            }
            $results = $json['results'] ?? [];

            foreach ($results as $run) {
                $runId = $run['id'] ?? $run['runId'] ?? null;

                if ($runId && isset($seenRunIds[$runId])) {
                    continue;
                }
                if ($runId) {
                    $seenRunIds[$runId] = true;
                }

                $aggregated->push([
                    'run_id' => $runId,
                    'flow_id' => $task['flow_id'],
                    'workspace_id' => $task['workspace_id'],
                    'workflow_key' => $task['key_name'],
                    'workflow_url' => $task['workflow_url'],
                    'status' => $run['status'] ?? $task['status'],
                    'error' => $this->resolveRunError($run),
                    'created_at' => $run['createdAt'] ?? $run['created_at'] ?? $run['startedAt'] ?? null,
                    'finished_at' => $run['finishedAt'] ?? $run['finished_at'] ?? $run['endedAt'] ?? null,
                    'contact_id' => $run['contactId'] ?? $run['contact_id'] ?? null,
                    // Payload fields are null until detail is loaded on-demand via View modal
                    'ref_id' => null,
                    'quote_uid' => null,
                    'workflow_type' => null,
                    'original_payload' => null,
                    'raw' => $run,
                ]);
            }
        }

        return $aggregated->sortByDesc('created_at')->values();
    }

    /**
     * Retrigger a single Bird API run identified by its workflow URL and run ID.
     * Merges the original run payload so the workflow receives all its original data.
     *
     * @param  array<string, mixed>|null  $originalPayload  The original body payload from the failed run
     *
     * @throws \RuntimeException
     */
    public function retriggerBirdApiRun(string $workflowUrl, string $runId, ?string $contactId = null, ?array $originalPayload = null): ?string
    {
        // Strip /invoke-sync suffix if present to get the base invoke URL
        $triggerUrl = rtrim($workflowUrl, '/');
        if (! str_ends_with($triggerUrl, 'invoke-sync')) {
            $triggerUrl .= '/invoke-sync';
        }

        // Merge original payload so the workflow receives all its original data,
        // then stamp retrigger metadata on top
        $payload = array_merge($originalPayload ?? [], [
            'admin_retrigger' => true,
            'retrigger_source' => 'admin_panel',
            'original_run_id' => $runId,
        ]);

        if ($contactId) {
            $payload['contactId'] = $contactId;
        }

        LoggerService::info('BirdWorkflowAdminService: Retriggering Bird API run', [
            'trigger_url' => $triggerUrl,
            'original_run_id' => $runId,
        ]);

        $response = $this->birdService->triggerWebHookRequest($triggerUrl, $payload, 'post', true);

        $newRunId = null;
        foreach (['Run-Id', 'run-id'] as $header) {
            if (isset($response->headers[$header])) {
                $val = $response->headers[$header];
                $newRunId = is_array($val) ? collect($val)->first() : $val;
                break;
            }
        }

        LoggerService::info('BirdWorkflowAdminService: Retrigger Bird API run completed', [
            'original_run_id' => $runId,
            'new_run_id' => $newRunId,
        ]);

        return $newRunId;
    }

    /**
     * Return raw Bird API response for one workflow (for debugging field structure).
     *
     * @return array<string, mixed>
     */
    public function debugRawBirdResponse(?string $workflowKey = null, int $limit = 1, string $status = 'failed'): array
    {
        $key = $workflowKey ?? $this->workflowUrlKeys()[0] ?? null;

        if (! $key) {
            return ['error' => 'No workflow keys configured'];
        }

        $storage = ApplicationStorage::where('key_name', $key)->first();

        if (! $storage || empty($storage->value)) {
            return ['error' => "Workflow key '{$key}' has no URL configured in ApplicationStorage"];
        }

        $parsed = $this->birdService->parseWorkflowUrl($storage->value);

        if (! $parsed) {
            return [
                'error' => 'Could not parse workspace_id / flow_id from URL',
                'url' => $storage->value,
            ];
        }

        $listResult = $this->birdService->getFlowRuns($parsed['workspace_id'], $parsed['flow_id'], [
            'status' => $status,
            'limit' => $limit,
        ]);

        $runs = $listResult['results'];
        $enriched = [];

        foreach ($runs as $run) {
            $runId = $run['id'] ?? $run['runId'] ?? null;
            $detail = $runId ? $this->birdService->getFlowRunDetail($parsed['workspace_id'], $parsed['flow_id'], $runId) : null;

            $enriched[] = [
                'list_keys' => array_keys($run),
                'detail_keys' => $detail ? array_keys($detail) : null,
                'list_run' => $run,
                'detail_run' => $detail,
                'resolved_body' => $this->resolveRunBody($detail ?? $run),
            ];
        }

        return [
            'workflow_key' => $key,
            'workflow_url' => $storage->value,
            'parsed' => $parsed,
            'status_filter' => $status,
            'run_count' => count($runs),
            'list_response_top_keys' => array_keys($listResult['raw_response'] ?? []),
            'runs' => $enriched,
        ];
    }

    /**
     * Return summary statistics for display on the admin page.
     *
     * @return array<string, int>
     */
    public function getSummaryStats(): array
    {
        $total = QuoteFlowDetails::count();
        $withRunId = QuoteFlowDetails::whereNotNull('flow_id')->where('flow_id', '!=', '')->count();
        $withoutRunId = $total - $withRunId;
        $today = QuoteFlowDetails::whereDate('started_at', today())->count();

        return [
            'total' => $total,
            'triggered_successfully' => $withRunId,
            'failed_trigger' => $withoutRunId,
            'today' => $today,
        ];
    }
}
