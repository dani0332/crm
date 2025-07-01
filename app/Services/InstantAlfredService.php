<?php

namespace App\Services;

use App\Enums\InstantChatReportsEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\AlfredChat;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstantAlfredService extends BaseService
{
    private $personalQuery;

    /**
     * Get the appropriate lead_assignment_trigger select statement based on quote type
     */
    private function getLeadAssignmentTriggerSelect($quoteTypeId): string
    {
        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
            case QuoteTypeId::Bike:
                return 'COALESCE(cqr.lead_assignment_trigger, pqr.lead_assignment_trigger) as lead_assignment_trigger';
            case QuoteTypeId::Health:
                return 'COALESCE(hqr.lead_assignment_trigger, pqr.lead_assignment_trigger) as lead_assignment_trigger';
            case QuoteTypeId::Travel:
                return 'COALESCE(tqr.lead_assignment_trigger, pqr.lead_assignment_trigger) as lead_assignment_trigger';
            default:
                return 'pqr.lead_assignment_trigger as lead_assignment_trigger';
        }
    }

    private function buildQueryByModel($quoteTypeId)
    {
        $request = request();

        // Check if we need full data or can use optimized query
        $needsFullData = $this->shouldUseFullQuery($request);

        if (! $needsFullData) {
            // Optimized query for initial page load - only basic fields
            $this->personalQuery = DB::table('personal_quotes as pqr')
                ->select(
                    'pqr.uuid',
                    'pqr.id',
                    'pqr.code',
                    'pqrd.chat_initiated_at'
                )
                ->where('pqr.quote_type_id', $quoteTypeId)
                ->when($request->email, function ($query) use ($request) {
                    $query->where('pqr.email', '=', $request->email);
                })
                ->when($request->mobile_no, function ($query) use ($request) {
                    $query->where('pqr.mobile_no', '=', $request->mobile_no);
                })
                ->when($request->quoteId, function ($query) use ($request) {
                    $query->where('pqr.code', '=', $request->quoteId);
                })
                ->leftJoin('personal_quote_details as pqrd', 'pqrd.personal_quote_id', '=', 'pqr.id')
                ->when(! empty($request->chat_initiated_at), function ($query) use ($request) {
                    $dateFrom = date('Y-m-d 00:00:00', strtotime($request->chat_initiated_at[0]));
                    $dateTo = date('Y-m-d 23:59:59', strtotime($request->chat_initiated_at[1]));
                    $query->whereBetween('pqrd.chat_initiated_at', [$dateFrom, $dateTo]);
                })
                ->when(isset($request->sortType), function ($query) use ($request) {
                    $query->orderBy('pqrd.chat_initiated_at', $request->sortType);
                });
        } else {
            // Full query with all joins and data when filters/reports are needed
            $aliases = [];

            // Define segment constants for better maintainability
            $SEGMENT_NON_SIC = 'NON-SIC';
            $SEGMENT_SIC_REVIVAL = 'SIC-REVIVAL';
            $SEGMENT_AIG = 'AIG';
            $SEGMENT_SIC = 'SIC';

            // Define revival sources for better maintainability
            $REVIVAL_SOURCES = [
                LeadSourceEnum::REVIVAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
            ];

            $subQuery = DB::table('quote_tags as qt')
                ->select(
                    'qt.quote_uuid',
                    DB::raw('GROUP_CONCAT(qt.name) as tags')
                )
                ->where('qt.quote_type_id', $quoteTypeId)
                ->groupBy('qt.quote_uuid');

            $this->personalQuery = DB::table('personal_quotes as pqr')
                ->select(
                    'pqr.uuid',
                    'pqr.id',
                    'pqr.email',
                    'pqr.code',
                    'pqr.payment_status_id',
                    'pqr.plan_id',
                    'pqr.quote_status_id',
                    'pqr.quote_batch_id',
                    'pqr.insurance_provider_id',
                    'pqr.premium as total_price',
                    DB::raw($this->getLeadAssignmentTriggerSelect($quoteTypeId)),
                    'pqrd.chat_initiated_at',
                    'qs.text AS quote_status_id_text',
                    'qb.name as quote_batch_id_text',
                    'lu.text as transaction_type_text',
                    'ps.text AS payment_status',
                    DB::raw('DATE_FORMAT(pqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
                    DB::raw('DATE_FORMAT(pqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
                    DB::raw('DATE_FORMAT(pqrd.advisor_assigned_date, "%d-%m-%Y %H:%i:%s") as advisor_assigned_date'),
                    DB::raw("
                        CASE 
                            -- Handle NULL tags first (most common case)
                            WHEN qt.tags IS NULL THEN '{$SEGMENT_NON_SIC}'
                            
                            -- Handle AIG cases first (most specific tag)
                            WHEN qt.tags LIKE '%".QuoteSegmentEnum::AIG->tag()."%' THEN '{$SEGMENT_AIG}'
                            
                            -- Handle SIC-REVIVAL cases (requires both tag and source match)
                            WHEN (
                                (qt.tags LIKE '%".QuoteSegmentEnum::SIC->tag()."%' OR qt.tags LIKE '%".QuoteSegmentEnum::SIC_REVIVAL->tag()."%')
                                AND pqr.source IN ('".implode("','", $REVIVAL_SOURCES)."')
                            ) THEN '{$SEGMENT_SIC_REVIVAL}'
                            
                            -- Handle SIC cases (excluding SIC-REVIVAL)
                            WHEN qt.tags LIKE '%".QuoteSegmentEnum::SIC->tag()."%' THEN '{$SEGMENT_SIC}'
                            
                            -- Handle NON-SIC cases explicitly (tags exist but don't contain SIC or AIG)
                            WHEN (
                                qt.tags NOT LIKE '%".QuoteSegmentEnum::SIC->tag()."%' 
                                AND qt.tags NOT LIKE '%".QuoteSegmentEnum::AIG->tag()."%'
                            ) THEN '{$SEGMENT_NON_SIC}'
                            
                            -- Default case (any other unexpected cases)
                            ELSE '{$SEGMENT_NON_SIC}'
                        END as segment
                    "),
                )
                ->where('pqr.quote_type_id', $quoteTypeId)
                ->when($request->email, function ($query) use ($request) {
                    $query->where('pqr.email', '=', $request->email);
                })
                ->when($request->mobile_no, function ($query) use ($request) {
                    $query->where('pqr.mobile_no', '=', $request->mobile_no);
                })
                ->when($request->quoteId, function ($query) use ($request) {
                    $query->where('pqr.code', '=', $request->quoteId);
                })
                ->leftJoin('personal_quote_details as pqrd', 'pqrd.personal_quote_id', '=', 'pqr.id')
                ->when(! empty($request->chat_initiated_at), function ($query) use ($request) {
                    $dateFrom = date('Y-m-d 00:00:00', strtotime($request->chat_initiated_at[0]));
                    $dateTo = date('Y-m-d 23:59:59', strtotime($request->chat_initiated_at[1]));
                    $query->whereBetween('pqrd.chat_initiated_at', [$dateFrom, $dateTo]);
                })
                ->leftJoin('payments as py', function ($join) {
                    $join->on('py.paymentable_id', '=', 'pqr.id')
                        ->where('py.paymentable_type', '=', PersonalQuote::class);
                })
                ->leftJoinSub($subQuery, 'qt', function ($join) {
                    $join->on('qt.quote_uuid', '=', 'pqr.uuid');
                })
                ->leftJoin('lookups as lu', 'lu.id', '=', 'pqr.transaction_type_id')
                ->leftJoin('payment_status as ps', 'ps.id', '=', 'pqr.payment_status_id')
                ->leftJoin('quote_status as qs', 'qs.id', '=', 'pqr.quote_status_id')
                ->leftJoin('quote_batches as qb', 'qb.id', '=', 'pqr.quote_batch_id')
                ->when($quoteTypeId == QuoteTypeId::Car || $quoteTypeId === QuoteTypeId::Bike, function ($query) {
                    $query->leftJoin('car_quote_request as cqr', 'cqr.uuid', '=', 'pqr.uuid');
                })
                ->when($quoteTypeId == QuoteTypeId::Health, function ($query) {
                    $query->leftJoin('health_quote_request as hqr', 'hqr.uuid', '=', 'pqr.uuid');
                })
                ->when($quoteTypeId == QuoteTypeId::Travel, function ($query) {
                    $query->leftJoin('travel_quote_request as tqr', 'tqr.uuid', '=', 'pqr.uuid');
                })
                ->when($quoteTypeId == QuoteTypeId::Car || $quoteTypeId === QuoteTypeId::Bike, function ($query) use ($quoteTypeId) {
                    $query->leftJoin('car_plan as cp', function ($join) use ($quoteTypeId) {
                        $join->on('cp.id', '=', 'pqr.plan_id')
                            ->where('cp.quote_type_id', '=', $quoteTypeId);
                    });
                    $query->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id');
                    $query->addSelect([
                        'cp.text AS plan_id_text',
                        'cpip.code as plan_provider_code',
                        'cpip.text as provider_name',
                        'cp.repair_type as plan_type',
                        'cp.text as plan_name',
                    ]);
                })
                ->when($quoteTypeId == QuoteTypeId::Health, function ($query) {
                    $query->leftJoin('health_plan as hp', 'hp.id', '=', 'pqr.plan_id');
                    $query->leftJoin('health_plan_type as hpt', 'hpt.id', '=', 'hp.plan_type_id');
                    $query->leftJoin('insurance_provider as ihp', 'ihp.id', '=', 'hp.provider_id');
                    $query->addSelect([
                        'hp.plan_type_id as plan_type_id',
                        'ihp.text as provider_name',
                        'hpt.text as plan_type',
                        'hp.text as plan_name',
                    ]);
                })
                ->when($quoteTypeId == QuoteTypeId::Travel, function ($query) {
                    $query->leftJoin('travel_plan as tp', 'tp.id', '=', 'pqr.plan_id');
                    $query->leftJoin('insurance_provider as tpip', 'tpip.id', '=', 'tp.provider_id');
                    $query->leftJoin('travel_quote_plan_details as tqpd', function ($join) {
                        $join->on('pqr.uuid', '=', 'tqpd.quote_uuid')
                            ->whereColumn('pqr.plan_id', '=', 'tqpd.plan_id');
                    });
                    $query->addSelect([
                        'tp.text AS plan_id_text',
                        'tpip.text AS travel_plan_provider_text',
                        'tp.travel_type as plan_type',
                        'tqpd.provider_name',
                        'tqpd.plan_name',
                    ]);
                })
                ->when($quoteTypeId == QuoteTypeId::Home, function ($query) {
                    $query->leftJoin('quote_customer_plans as qcp', 'qcp.quote_uuid', '=', 'pqr.uuid');
                    $query->addSelect([
                        DB::raw("JSON_UNQUOTE(qcp.plan->'$.providerName') as provider_name"),
                        DB::raw("JSON_UNQUOTE(qcp.plan->'$.name') as plan_name"),
                    ]);
                })
                ->groupBy('pqr.id')
                ->when(isset($request->sortType), function ($query) use ($request) {
                    $query->orderBy('pqrd.chat_initiated_at', $request->sortType);
                });
        }

        $aliases = [PersonalQuote::class => ['query' => $this->personalQuery, 'alias' => 'pqr']];

        return $aliases;
    }

    /**
     * Determine if we should use the full query or optimized simple query
     */
    private function shouldUseFullQuery($request): bool
    {
        // Use full query if report type is specified (needed for exports)
        if (! empty($request->report)) {
            return true;
        }

        // Use full query if any complex filters are applied that need additional data
        $complexFilters = [
            'transaction_type_id',
            'quote_batch_id',
            'quote_status_id',
            'payment_status_id',
            'sale_leads',
            'segment',
            'assignment_type',
        ];

        foreach ($complexFilters as $filter) {
            if (! empty($request->$filter)) {
                return true;
            }
        }

        // Use simple query for basic filters (these only need uuid, code, chat_initiated_at)
        return false;
    }

    public function processSqlChatFilters(Request $request)
    {

        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
        $modelType = (checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $aliases = $this->buildQueryByModel($quoteTypeId);

        $modelData = $aliases[PersonalQuote::class] ?? $aliases[CarQuote::class];

        $alias = $modelData['alias'];

        $partialQuery = $modelData['query'];

        $partialQuery->whereNotNull('chat_initiated_at');

        if ($request->email == null && $request->mobile_no == null && $request->quoteId == null && empty($request->chat_initiated_at)) {
            // Default to current day if no dates are provided
            $dateFrom = now()->startOfDay();
            $dateTo = now()->endOfDay();

            $partialQuery->whereBetween('pqrd.chat_initiated_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->transaction_type_id) && $request->transaction_type_id != '') {
            $partialQuery->whereIn('pqr.transaction_type_id', $request->transaction_type_id);
        }

        if (isset($request->quote_batch_id) && ! empty($request->quote_batch_id)) {
            $partialQuery->whereIn('pqr.quote_batch_id', $request->quote_batch_id);
        }

        if (isset($request->quote_status_id) && is_array($request->quote_status_id) && count($request->quote_status_id) > 0) {
            $partialQuery->whereIn('pqr.quote_status_id', $request->quote_status_id);
        }

        if (isset($request->payment_status_id) && $request->payment_status_id != '') {
            $partialQuery->whereIn('pqr.payment_status_id', $request->payment_status_id);
        }

        if (in_array($modelType, [HealthQuote::class, CarQuote::class]) && isset($request->assigment_type) && $request->assigment_type != '') {
            $partialQuery->where('pqr.assignment_type', $request->assigment_type);
        }

        if (isset($request->sale_leads) && $request->sale_leads != '') {
            if ($request->sale_leads == quoteTypeCode::yesText) {
                $partialQuery->whereIn('pqr.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]);
            }
            if ($request->sale_leads == quoteTypeCode::noText) {
                $partialQuery->whereNotNull('pqr.quote_status_id');
            }
        }

        if (isset($request->segment) && $request->segment != 'all' && $request->segment != '') {
            $partialQuery->having('segment', '=', $request->segment);
        }

        return $partialQuery;
    }

    /**
     * Get the base query builder for consolidated chat reports (chunked processing)
     * This method returns a query builder instead of executing it, allowing for chunked processing
     */
    public function getChatConsolidateReportQuery(array $requestParams = [])
    {
        // Create a request instance from parameters if not available
        $request = $this->createRequestFromParams($requestParams);

        return $this->processSqlChatFilters($request);
    }

    /**
     * Get the base query builder for detailed chat reports (chunked processing)
     * Returns the same SQL query as consolidated reports since the base data comes from SQL
     */
    public function getChatDetailedReportQuery(array $requestParams = [])
    {
        // Create a request instance from parameters if not available
        $request = $this->createRequestFromParams($requestParams);

        return $this->processSqlChatFilters($request);
    }

    /**
     * Create a request instance from parameters array
     * This helps support both request() and parameter-based processing
     */
    private function createRequestFromParams(array $requestParams = [])
    {
        $currentRequest = request();

        // If we have parameters, merge them with current request
        if (! empty($requestParams)) {
            // Create new request instance with merged data
            $mergedData = array_merge($currentRequest->all(), $requestParams);
            $currentRequest->merge($mergedData);
        }

        return $currentRequest;
    }

    public function generateChatConsolidateReport()
    {

        $request = request();

        $data = $this->processSqlChatFilters($request)->get();

        $data->chunk(1000)->each(function ($sqlBatch) use ($request) {
            $uuids = $sqlBatch->pluck('uuid')->toArray();

            $mongoPipeline = $this->createPipeline($request, $uuids, $request->report);

            $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($mongoPipeline))->toArray();

            $mongoResultsCollection = collect($mongoResults);
            foreach ($sqlBatch as $sqlRecord) {

                $relatedMongoRecord = $mongoResultsCollection->firstWhere('id', $sqlRecord->uuid);

                $sqlRecord->quote_type = $request->quoteType;
                $sqlRecord->lead_assignment_trigger_text = $sqlRecord->lead_assignment_trigger
                    ? LeadAssignmentTriggerEnum::getAssignmentTypeText($sqlRecord->lead_assignment_trigger)
                    : 'N/A';

                if ($relatedMongoRecord) {
                    $sqlRecord->communication_channels = $relatedMongoRecord['communication_channels'];
                    $sqlRecord->customer_interactions = $relatedMongoRecord['customer_interactions'];
                    $sqlRecord->ai_interactions = $relatedMongoRecord['ai_interactions'];
                    $sqlRecord->total_ai_interactions = $relatedMongoRecord['total_ai_interactions'];
                    $sqlRecord->fallbacks = $relatedMongoRecord['fallbacks'];
                    $sqlRecord->date_of_first_interaction = $relatedMongoRecord['date_of_first_interaction'];
                }
            }

        });

        return $data;
    }

    /**
     * Process a chunk of consolidated chat data (used by chunked CSV export)
     * This method processes MongoDB data for a chunk of SQL records
     */
    public function processConsolidatedChunk($sqlRecords, array $requestParams = [])
    {
        $request = $this->createRequestFromParams($requestParams);

        // Extract UUIDs from the chunk
        $uuids = collect($sqlRecords)->pluck('uuid')->toArray();

        if (empty($uuids)) {
            return collect($sqlRecords);
        }

        // Create MongoDB pipeline for this chunk
        $mongoPipeline = $this->createPipeline($request, $uuids, $request->report ?? 'consolidated');

        try {
            // Get MongoDB results for this chunk
            $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($mongoPipeline))->toArray();
            $mongoResultsCollection = collect($mongoResults);

            // Merge SQL and MongoDB data
            foreach ($sqlRecords as $sqlRecord) {
                $relatedMongoRecord = $mongoResultsCollection->firstWhere('_id', $sqlRecord->uuid);

                // Set quote type
                $sqlRecord->quote_type = $request->quoteType ?? explode('-', $sqlRecord->code ?? '')[0] ?? 'N/A';

                // Set lead assignment trigger text
                $sqlRecord->lead_assignment_trigger_text = $sqlRecord->lead_assignment_trigger
                    ? LeadAssignmentTriggerEnum::getAssignmentTypeText($sqlRecord->lead_assignment_trigger)
                    : 'N/A';

                // Merge MongoDB data if available
                if ($relatedMongoRecord) {
                    $sqlRecord->communication_channels = $relatedMongoRecord['communication_channels'] ?? [];
                    $sqlRecord->customer_interactions = $relatedMongoRecord['customer_interactions'] ?? 0;
                    $sqlRecord->ai_interactions = $relatedMongoRecord['ai_interactions'] ?? 0;
                    $sqlRecord->total_ai_interactions = $relatedMongoRecord['total_ai_interactions'] ?? 0;
                    $sqlRecord->fallbacks = $relatedMongoRecord['fallbacks'] ?? 0;
                    $sqlRecord->date_of_first_interaction = $relatedMongoRecord['date_of_first_interaction'] ?? 'N/A';
                } else {
                    // Set default values if no MongoDB data found
                    $sqlRecord->communication_channels = [];
                    $sqlRecord->customer_interactions = 0;
                    $sqlRecord->ai_interactions = 0;
                    $sqlRecord->total_ai_interactions = 0;
                    $sqlRecord->fallbacks = 0;
                    $sqlRecord->date_of_first_interaction = 'N/A';
                }
            }
        } catch (\Exception $e) {
            // Log error but continue processing with default values
            \Illuminate\Support\Facades\Log::error('MongoDB processing failed for consolidated chunk', [
                'uuids_count' => count($uuids),
                'error' => $e->getMessage(),
            ]);

            // Set default values for all records in chunk
            foreach ($sqlRecords as $sqlRecord) {
                $sqlRecord->quote_type = $request->quoteType ?? 'N/A';
                $sqlRecord->lead_assignment_trigger_text = 'N/A';
                $sqlRecord->communication_channels = [];
                $sqlRecord->customer_interactions = 0;
                $sqlRecord->ai_interactions = 0;
                $sqlRecord->total_ai_interactions = 0;
                $sqlRecord->fallbacks = 0;
                $sqlRecord->date_of_first_interaction = 'N/A';
            }
        }

        return collect($sqlRecords);
    }

    /**
     * Process a chunk of detailed chat data (used by chunked CSV export)
     * This method processes MongoDB data for a chunk of SQL records to get detailed chat messages
     */
    public function processDetailedChunk($sqlRecords, array $requestParams = [])
    {
        $request = $this->createRequestFromParams($requestParams);

        // Ensure report type is set for detailed processing
        if (! isset($request->report)) {
            $request->merge(['report' => InstantChatReportsEnum::DETAILED_REPORT]);
        }

        // Extract UUIDs from the chunk
        $uuids = collect($sqlRecords)->pluck('uuid')->toArray();

        if (empty($uuids)) {
            return collect();
        }

        try {
            // Create MongoDB pipeline for detailed reports
            $mongoPipeline = $this->createPipeline($request, $uuids, InstantChatReportsEnum::DETAILED_REPORT);

            // Get MongoDB results for this chunk
            $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($mongoPipeline))->toArray();

            // Create mappings for SQL data to merge with MongoDB results
            $sqlData = collect($sqlRecords)->keyBy('uuid');

            // Process MongoDB results and merge with SQL data
            $processedResults = collect($mongoResults)->map(function ($record) use ($sqlData) {
                $quoteId = $record['quote_id'] ?? null;
                if ($quoteId && isset($sqlData[$quoteId])) {
                    // Add segment from SQL data
                    $record['segment'] = $sqlData[$quoteId]->segment ?? 'N/A';

                    // Add lead_assignment_trigger and its text representation
                    $record['lead_assignment_trigger'] = $sqlData[$quoteId]->lead_assignment_trigger ?? null;
                    $record['lead_assignment_trigger_text'] = $sqlData[$quoteId]->lead_assignment_trigger
                        ? LeadAssignmentTriggerEnum::getAssignmentTypeText($sqlData[$quoteId]->lead_assignment_trigger)
                        : 'N/A';
                }

                return $record;
            });

            return $processedResults;

        } catch (\Exception $e) {
            // Log error but continue processing with empty collection
            \Illuminate\Support\Facades\Log::error('MongoDB processing failed for detailed chunk', [
                'uuids_count' => count($uuids),
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    public function generateChatDetailedReport()
    {
        $request = request();

        $data = $this->processSqlChatFilters($request)->get();

        $uuids = array_column($data->toArray(), 'uuid');

        $chunkSize = 1000;

        $uuidChunks = array_chunk($uuids, $chunkSize);

        $mongoResults = collect();

        foreach ($uuidChunks as $chunk) {

            $mongoPipeline = $this->createPipeline($request, $chunk, $request->report);

            $chunkResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($mongoPipeline));

            $mongoResults = $mongoResults->merge(collect($chunkResults));
        }

        // Create mappings for SQL data to merge with MongoDB results
        $sqlData = $data->keyBy('uuid');

        // Add SQL data to mongo results
        $mongoResults = $mongoResults->map(function ($record) use ($sqlData) {
            $quoteId = $record['quote_id'] ?? null;
            if ($quoteId && isset($sqlData[$quoteId])) {
                // Add segment
                $record['segment'] = $sqlData[$quoteId]->segment ?? 'N/A';

                // Add lead_assignment_trigger and its text representation
                $record['lead_assignment_trigger'] = $sqlData[$quoteId]->lead_assignment_trigger ?? null;
                $record['lead_assignment_trigger_text'] = $sqlData[$quoteId]->lead_assignment_trigger
                    ? LeadAssignmentTriggerEnum::getAssignmentTypeText($sqlData[$quoteId]->lead_assignment_trigger)
                    : 'N/A';
            }

            return $record;
        });

        return $mongoResults;
    }

    public function createPipeline(Request $request, $itemIds, $type)
    {
        $pipeline[] = [
            '$match' => [
                'quote_id' => ['$in' => $itemIds],
            ],
        ];

        if ($type === 'chat') {
            $pipeline[] = [
                '$group' => [
                    '_id' => '$quote_id',
                    'created_at' => ['$first' => '$created_at'],
                    'communication_channels' => ['$addToSet' => [
                        '$cond' => [
                            ['$ifNull' => ['$channel', false]],
                            '$channel',
                            '$$REMOVE',
                        ],
                    ]],
                    'fallback' => ['$first' => '$fallback'],
                ],
            ];
        } elseif ($request->report == InstantChatReportsEnum::DETAILED_REPORT) {
            $pipeline[] = [
                '$project' => [
                    'created_at' => 1,
                    'role' => 1,
                    'msg' => 1,
                    'quote_id' => 1,
                    'quote_type' => $request->quoteType,
                    'employee_flag' => '$who_chatted.is_employee',
                    'email' => '$who_chatted.email',
                    'user_system' => '$who_chatted.user_agent',
                    'user_ip_address' => '$who_chatted.ip',
                    'communication_channel' => '$channel',
                    'input_tokens_usage' => '$response.usage.prompt_tokens',
                    'completion_tokens' => '$response.usage.completion_tokens',
                    'total_tokens' => '$response.usage.total_tokens',
                ],
            ];
            $pipeline[] = [
                '$sort' => [
                    'created_at' => $request->sortType == 'desc' ? -1 : 1,
                ],
            ];
        } elseif ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $pipeline[] = [
                '$group' => [
                    '_id' => '$quote_id',
                    'quote_type' => ['$last' => '$quote_type'],
                    'date_of_first_interaction' => ['$min' => '$created_at'],
                    'communication_channels' => ['$addToSet' => [
                        '$cond' => [
                            ['$ifNull' => ['$channel', false]],
                            '$channel',
                            '$$REMOVE',
                        ],
                    ]],
                    'customer_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$role', 'USER']],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'ai_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$role', 'AI']],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'total_ai_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$in' => ['$role', ['AI', 'USER']]],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'fallbacks' => [
                        '$sum' => ['$cond' => [['$ifNull' => ['$fallback', false]], 1, 0]],
                    ],
                ],
            ];
            $pipeline[] = [
                '$sort' => [
                    'date_of_first_interaction' => $request->sortType == 'desc' ? -1 : 1,
                ],
            ];
        }

        return $pipeline;
    }
}
