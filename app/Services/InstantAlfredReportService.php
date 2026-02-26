<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\AlfredChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Optimized SQL service for Instant Alfred exports.
 *
 * Key differences from InstantAlfredService:
 *  - Removes lookup JOINs (payment_status, quote_status, lookups, quote_batches)
 *    and resolves their text values via PHP maps instead.
 *  - Removes the quote_tags GROUP_CONCAT subquery from the main query.
 *    Tags are fetched per-batch (scoped to the actual UUIDs) and segment
 *    is calculated in PHP using str_contains() — no SQL LIKE or HAVING.
 *  - The main query only keeps joins that cannot be moved to PHP:
 *    quote-type-specific tables (car/health/travel) for lead_assignment_trigger
 *    and plan data (provider, plan type, plan name).
 */
class InstantAlfredReportService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns an optimized query builder used as the SQL base for both reports.
     * Removed JOINs: lookups, payment_status, quote_status, quote_batches, quote_tags.
     */
    public function getReportQuery(array $params)
    {
        $request     = $this->buildRequest($params);
        $quoteTypeId = $this->resolveQuoteTypeId($request->quoteType ?? 'Car');

        return $this->buildCoreQuery($quoteTypeId, $request);
    }

    /**
     * Returns the quoteTypeId resolved from the params array.
     */
    public function resolveQuoteTypeIdFromParams(array $params): int
    {
        return $this->resolveQuoteTypeId($params['quoteType'] ?? request()->quoteType ?? 'Car');
    }

    /**
     * Processes a batch of SQL records for the Consolidated report.
     * Fetches MongoDB $group aggregation, merges with SQL data, and
     * enriches with PHP-resolved lookup text + segment.
     *
     * @param  array<\stdClass>  $sqlRecords
     */
    public function processConsolidatedChunk(array $sqlRecords, array $params): \Illuminate\Support\Collection
    {
        if (empty($sqlRecords)) {
            return collect();
        }

        $request     = $this->buildRequest($params);
        $quoteTypeId = $this->resolveQuoteTypeId($request->quoteType ?? 'Car');
        $uuids       = array_column($sqlRecords, 'uuid');

        $lookupMaps  = $this->loadLookupMaps();
        $tagsByUuid  = $this->fetchTagsByUuids($uuids, $quoteTypeId);
        $segmentFilter = $this->getSegmentFilter($request);

        $pipeline = $this->buildConsolidatedPipeline($uuids, $request);

        try {
            $mongoResults = AlfredChat::raw(fn ($c) => $c->aggregate($pipeline))->toArray();
            $mongoByUuid  = collect($mongoResults)->keyBy('id');
        } catch (\Exception $e) {
            Log::error('[ConsolidatedChunk] MongoDB aggregation failed', [
                'uuid_count' => count($uuids),
                'error'      => $e->getMessage(),
            ]);
            $mongoByUuid = collect();
        }

        $result = [];

        foreach ($sqlRecords as $sqlRecord) {
            $mongo   = $mongoByUuid->get($sqlRecord->uuid);
            $segment = $this->resolveSegment($tagsByUuid[$sqlRecord->uuid] ?? null, $sqlRecord->source ?? '');

            // PHP-based segment filter (replaces SQL HAVING)
            if ($segmentFilter !== null && $segment !== $segmentFilter) {
                continue;
            }

            $sqlRecord->quote_type               = $request->quoteType ?? explode('-', $sqlRecord->code ?? '')[0] ?? 'N/A';
            $sqlRecord->lead_assignment_trigger_text = $sqlRecord->lead_assignment_trigger
                ? LeadAssignmentTriggerEnum::getAssignmentTypeText($sqlRecord->lead_assignment_trigger)
                : 'N/A';

            // PHP-resolved lookup text (replaces lookup JOINs)
            $sqlRecord->payment_status        = $lookupMaps['payment_statuses'][$sqlRecord->payment_status_id ?? '']   ?? 'N/A';
            $sqlRecord->quote_status_id_text  = $lookupMaps['quote_statuses'][$sqlRecord->quote_status_id ?? '']       ?? 'N/A';
            $sqlRecord->quote_batch_id_text   = $lookupMaps['quote_batches'][$sqlRecord->quote_batch_id ?? '']         ?? 'N/A';
            $sqlRecord->transaction_type_text = $lookupMaps['transaction_types'][$sqlRecord->transaction_type_id ?? ''] ?? 'N/A';
            $sqlRecord->segment               = $segment;

            if ($mongo) {
                $sqlRecord->communication_channels    = $mongo['communication_channels']    ?? [];
                $sqlRecord->customer_interactions     = $mongo['customer_interactions']     ?? 0;
                $sqlRecord->ai_interactions           = $mongo['ai_interactions']           ?? 0;
                $sqlRecord->total_ai_interactions     = $mongo['total_ai_interactions']     ?? 0;
                $sqlRecord->fallbacks                 = $mongo['fallbacks']                 ?? 0;
                $sqlRecord->date_of_first_interaction = $mongo['date_of_first_interaction'] ?? 'N/A';
            } else {
                $sqlRecord->communication_channels    = [];
                $sqlRecord->customer_interactions     = 0;
                $sqlRecord->ai_interactions           = 0;
                $sqlRecord->total_ai_interactions     = 0;
                $sqlRecord->fallbacks                 = 0;
                $sqlRecord->date_of_first_interaction = 'N/A';
            }

            $result[] = $sqlRecord;
        }

        return collect($result);
    }

    /**
     * Enriches the Detailed report SQL data array (keyed by uuid) with:
     *  - PHP-resolved lookup text (payment_status, quote_status, quote_batch, transaction_type)
     *  - segment calculated in PHP from tags
     * Applies the segment filter if present, removing non-matching UUIDs.
     *
     * @param  array<string, array<string, mixed>>  $sqlData  UUID-keyed array from fetchDetailedReportSqlData
     */
    public function enrichDetailedSqlData(array $sqlData, array $params): array
    {
        if (empty($sqlData)) {
            return $sqlData;
        }

        $request       = $this->buildRequest($params);
        $quoteTypeId   = $this->resolveQuoteTypeId($request->quoteType ?? 'Car');
        $uuids         = array_keys($sqlData);
        $segmentFilter = $this->getSegmentFilter($request);

        $lookupMaps = $this->loadLookupMaps();
        $tagsByUuid = $this->fetchTagsByUuids($uuids, $quoteTypeId);

        foreach ($sqlData as $uuid => &$record) {
            $record['payment_status']        = $lookupMaps['payment_statuses'][$record['payment_status_id'] ?? '']    ?? 'N/A';
            $record['quote_status_id_text']  = $lookupMaps['quote_statuses'][$record['quote_status_id'] ?? '']        ?? 'N/A';
            $record['quote_batch_id_text']   = $lookupMaps['quote_batches'][$record['quote_batch_id'] ?? '']          ?? 'N/A';
            $record['transaction_type_text'] = $lookupMaps['transaction_types'][$record['transaction_type_id'] ?? ''] ?? 'N/A';
            $record['segment']               = $this->resolveSegment($tagsByUuid[$uuid] ?? null, $record['source'] ?? '');
        }
        unset($record);

        if ($segmentFilter !== null) {
            $sqlData = array_filter($sqlData, fn ($r) => ($r['segment'] ?? '') === $segmentFilter);
        }

        return $sqlData;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core SQL Query (optimized — no lookup JOINs, no quote_tags subquery)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildCoreQuery(int $quoteTypeId, Request $request)
    {
        $query = DB::table('personal_quotes as pqr')
            ->select($this->coreSelectFields())
            ->where('pqr.quote_type_id', $quoteTypeId)
            ->leftJoin('personal_quote_details as pqrd', 'pqrd.personal_quote_id', '=', 'pqr.id')
            ->whereNotNull('pqrd.chat_initiated_at');

        $this->applyDateFilters($query, $request);
        $this->applyScalarFilters($query, $request);
        $this->addQuoteTypeJoins($query, $quoteTypeId);

        $query->groupBy('pqr.id');

        if (! empty($request->sortType)) {
            $query->orderBy('pqrd.chat_initiated_at', $request->sortType);
        }

        return $query;
    }

    private function coreSelectFields(): array
    {
        return [
            'pqr.uuid',
            'pqr.id',
            'pqr.email',
            'pqr.code',
            'pqr.source',
            'pqr.payment_status_id',
            'pqr.quote_status_id',
            'pqr.transaction_type_id',
            'pqr.quote_batch_id',
            'pqr.renewal_batch as renewal_batch_text',
            'pqr.premium as total_price',
            'pqr.insurance_provider_id',
            'pqr.plan_id',
            'pqr.created_at as lead_created_at',
            'pqrd.chat_initiated_at',
            DB::raw('DATE_FORMAT(pqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
            DB::raw('DATE_FORMAT(pqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
            DB::raw('DATE_FORMAT(pqrd.advisor_assigned_date, "%d-%m-%Y %H:%i:%s") as advisor_assigned_date'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Filter Application
    // ─────────────────────────────────────────────────────────────────────────

    private function applyDateFilters($query, Request $request): void
    {
        if (! empty($request->lead_created_at)) {
            $query->whereBetween('pqr.created_at', [
                Carbon::parse($request->lead_created_at[0]),
                Carbon::parse($request->lead_created_at[1]),
            ]);
        }

        if (! empty($request->chat_initiated_at)) {
            $query->whereBetween('pqrd.chat_initiated_at', [
                date('Y-m-d 00:00:00', strtotime($request->chat_initiated_at[0])),
                date('Y-m-d 23:59:59', strtotime($request->chat_initiated_at[1])),
            ]);
        }

        // Default to current day when no date/identifier filters are set
        $noFilters = empty($request->email)
            && empty($request->mobile_no)
            && empty($request->quoteId)
            && empty($request->chat_initiated_at)
            && empty($request->lead_created_at)
            && (is_null($request->renewal_batch) || (is_array($request->renewal_batch) && empty($request->renewal_batch)));

        if ($noFilters) {
            $query->whereBetween('pqrd.chat_initiated_at', [now()->startOfDay(), now()->endOfDay()]);
        }
    }

    private function applyScalarFilters($query, Request $request): void
    {
        if (! empty($request->email)) {
            $query->where('pqr.email', $request->email);
        }

        if (! empty($request->mobile_no)) {
            $query->where('pqr.mobile_no', $request->mobile_no);
        }

        if (! empty($request->quoteId)) {
            $query->where('pqr.code', $request->quoteId);
        }

        if (! empty($request->transaction_type_id)) {
            $query->whereIn('pqr.transaction_type_id', (array) $request->transaction_type_id);
        }

        if (! empty($request->quote_batch_id)) {
            $query->whereIn('pqr.quote_batch_id', (array) $request->quote_batch_id);
        }

        if (! empty($request->renewal_batch)) {
            is_array($request->renewal_batch)
                ? $query->whereIn('pqr.renewal_batch', $request->renewal_batch)
                : $query->where('pqr.renewal_batch', $request->renewal_batch);
        }

        if (! empty($request->quote_status_id) && is_array($request->quote_status_id)) {
            $query->whereIn('pqr.quote_status_id', $request->quote_status_id);
        }

        if (! empty($request->payment_status_id)) {
            $query->whereIn('pqr.payment_status_id', (array) $request->payment_status_id);
        }

        if (! empty($request->sale_leads)) {
            if ($request->sale_leads === quoteTypeCode::yesText) {
                $query->whereIn('pqr.quote_status_id', [
                    QuoteStatusEnum::TransactionApproved,
                    QuoteStatusEnum::PolicyIssued,
                    QuoteStatusEnum::PolicySentToCustomer,
                    QuoteStatusEnum::PolicyBooked,
                ]);
            } elseif ($request->sale_leads === quoteTypeCode::noText) {
                $query->whereNotNull('pqr.quote_status_id');
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quote-Type Joins (lead_assignment_trigger + plan data only)
    // ─────────────────────────────────────────────────────────────────────────

    private function addQuoteTypeJoins($query, int $quoteTypeId): void
    {
        $this->addLeadAssignmentJoin($query, $quoteTypeId);
        $this->addPlanJoin($query, $quoteTypeId);
    }

    private function addLeadAssignmentJoin($query, int $quoteTypeId): void
    {
        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
            case QuoteTypeId::Bike:
                $query->leftJoin('car_quote_request as cqr', 'cqr.uuid', '=', 'pqr.uuid')
                    ->addSelect(DB::raw('COALESCE(cqr.lead_assignment_trigger, pqr.lead_assignment_trigger) as lead_assignment_trigger'));
                break;
            case QuoteTypeId::Health:
                $query->leftJoin('health_quote_request as hqr', 'hqr.uuid', '=', 'pqr.uuid')
                    ->addSelect(DB::raw('COALESCE(hqr.lead_assignment_trigger, pqr.lead_assignment_trigger) as lead_assignment_trigger'));
                break;
            case QuoteTypeId::Travel:
                $query->leftJoin('travel_quote_request as tqr', 'tqr.uuid', '=', 'pqr.uuid')
                    ->addSelect(DB::raw('COALESCE(tqr.lead_assignment_trigger, pqr.lead_assignment_trigger) as lead_assignment_trigger'));
                break;
            default:
                $query->addSelect('pqr.lead_assignment_trigger');
                break;
        }
    }

    private function addPlanJoin($query, int $quoteTypeId): void
    {
        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
            case QuoteTypeId::Bike:
                $query->leftJoin('car_plan as cp', function ($join) use ($quoteTypeId) {
                    $join->on('cp.id', '=', 'pqr.plan_id')
                        ->where('cp.quote_type_id', '=', $quoteTypeId);
                })
                    ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
                    ->addSelect(['cp.text AS plan_id_text', 'cpip.code as plan_provider_code', 'cpip.text as provider_name', 'cp.repair_type as plan_type', 'cp.text as plan_name']);
                break;

            case QuoteTypeId::Health:
                $query->leftJoin('health_plan as hp', 'hp.id', '=', 'pqr.plan_id')
                    ->leftJoin('health_plan_type as hpt', 'hpt.id', '=', 'hp.plan_type_id')
                    ->leftJoin('insurance_provider as ihp', 'ihp.id', '=', 'hp.provider_id')
                    ->addSelect(['hp.plan_type_id as plan_type_id', 'ihp.text as provider_name', 'hpt.text as plan_type', 'hp.text as plan_name']);
                break;

            case QuoteTypeId::Travel:
                $query->leftJoin('travel_plan as tp', 'tp.id', '=', 'pqr.plan_id')
                    ->leftJoin('insurance_provider as tpip', 'tpip.id', '=', 'tp.provider_id')
                    ->leftJoin('travel_quote_plan_details as tqpd', function ($join) {
                        $join->on('pqr.uuid', '=', 'tqpd.quote_uuid')
                            ->whereColumn('pqr.plan_id', '=', 'tqpd.plan_id');
                    })
                    ->addSelect(['tp.text AS plan_id_text', 'tpip.text AS travel_plan_provider_text', 'tp.travel_type as plan_type', 'tqpd.provider_name', 'tqpd.plan_name']);
                break;

            case QuoteTypeId::Home:
                $query->leftJoin('quote_customer_plans as qcp', 'qcp.quote_uuid', '=', 'pqr.uuid')
                    ->addSelect([
                        DB::raw("JSON_UNQUOTE(qcp.plan->'$.providerName') as provider_name"),
                        DB::raw("JSON_UNQUOTE(qcp.plan->'$.name') as plan_name"),
                    ]);
                break;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHP Lookup Maps (replaces JOINs to tiny reference tables)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Loads all small reference tables into memory once per request.
     * These tables typically have < 100 rows each, making PHP maps
     * far cheaper than joining them to a 5k-50k row result set.
     */
    private function loadLookupMaps(): array
    {
        return [
            'payment_statuses'  => DB::table('payment_status')->pluck('text', 'id'),
            'quote_statuses'    => DB::table('quote_status')->pluck('text', 'id'),
            'quote_batches'     => DB::table('quote_batches')->pluck('name', 'id'),
            'transaction_types' => DB::table('lookups')->pluck('text', 'id'),
        ];
    }

    /**
     * Fetches GROUP_CONCAT tags only for the given UUIDs.
     * Scoping to specific UUIDs avoids a full table scan on quote_tags,
     * unlike the old subquery which joined the entire table.
     *
     * @return array<string, string>  uuid → comma-separated tags
     */
    private function fetchTagsByUuids(array $uuids, int $quoteTypeId): array
    {
        if (empty($uuids)) {
            return [];
        }

        return DB::table('quote_tags')
            ->select('quote_uuid', DB::raw('GROUP_CONCAT(name) as tags'))
            ->where('quote_type_id', $quoteTypeId)
            ->whereIn('quote_uuid', $uuids)
            ->groupBy('quote_uuid')
            ->pluck('tags', 'quote_uuid')
            ->toArray();
    }

    /**
     * Calculates the segment label in PHP — replaces the LIKE-based SQL CASE statement.
     * Follows the same priority order as the original SQL:
     *   AIG → SIC-REVIVAL → SIC → NON-SIC
     */
    private function resolveSegment(?string $tags, string $source): string
    {
        if (empty($tags)) {
            return 'NON-SIC';
        }

        $tagsLower     = strtolower($tags);
        $aigTag        = strtolower(QuoteSegmentEnum::AIG->tag());
        $sicTag        = strtolower(QuoteSegmentEnum::SIC->tag());
        $sicRevivalTag = strtolower(QuoteSegmentEnum::SIC_REVIVAL->tag());
        $revivalSources = [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID];

        if (str_contains($tagsLower, $aigTag)) {
            return 'AIG';
        }

        if (
            (str_contains($tagsLower, $sicTag) || str_contains($tagsLower, $sicRevivalTag))
            && in_array($source, $revivalSources)
        ) {
            return 'SIC-REVIVAL';
        }

        if (str_contains($tagsLower, $sicTag)) {
            return 'SIC';
        }

        return 'NON-SIC';
    }

    private function getSegmentFilter(Request $request): ?string
    {
        $segment = $request->segment ?? null;

        return ($segment && $segment !== 'all') ? $segment : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MongoDB Pipeline (Consolidated)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildConsolidatedPipeline(array $uuids, Request $request): array
    {
        return [
            ['$match' => ['quote_id' => ['$in' => $uuids]]],
            ['$group' => [
                '_id'                        => '$quote_id',
                'date_of_first_interaction'  => ['$min' => '$created_at'],
                'communication_channels'     => ['$addToSet' => '$channel'],
                'customer_interactions'      => ['$sum' => ['$cond' => [['$eq' => ['$role', 'USER']], 1, 0]]],
                'ai_interactions'            => ['$sum' => ['$cond' => [['$eq' => ['$role', 'AI']], 1, 0]]],
                'total_ai_interactions'      => ['$sum' => ['$cond' => [['$in' => ['$role', ['AI', 'USER']]], 1, 0]]],
                'fallbacks'                  => ['$sum' => ['$cond' => [['$ifNull' => ['$fallback', false]], 1, 0]]],
            ]],
            ['$sort' => ['date_of_first_interaction' => ($request->sortType === 'desc') ? -1 : 1]],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveQuoteTypeId(string $quoteType): int
    {
        return collect(QuoteTypeId::getOptions())->search(ucfirst($quoteType));
    }

    private function buildRequest(array $params): Request
    {
        $current = request();

        if (! empty($params)) {
            $current->merge(array_merge($current->all(), $params));
        }

        return $current;
    }
}
