<?php

namespace App\Services;

use App\Enums\QuoteSyncStatus;
use App\Enums\QuoteTypes;
use App\Models\QuoteSync;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuoteSyncService extends BaseService
{
    public function getData($filters)
    {
        $dataset = QuoteSync::when(isset($filters['quote_type']), function ($query) use ($filters) {
            $query->where('quote_sync.quote_type_id', $filters['quote_type']);
        })
            ->when(isset($filters['distinct']), function ($query) use ($filters) {
                if ($filters['distinct'] == 1) {
                    $query->groupBy('quote_sync.quote_uuid');
                }
            })
            ->when(isset($filters['uuid']), function ($query) use ($filters) {
                $query->where('quote_sync.quote_uuid', $filters['uuid']);
            })
            ->when(isset($filters['is_synced']), function ($query) use ($filters) {
                $query->where('quote_sync.is_synced', $filters['is_synced']);
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('quote_sync.status', $filters['status']);
            })
            ->when(isset($filters['synced_at']), function ($query) use ($filters) {
                $startDate = Carbon::parse($filters['synced_at'][0])->startOfDay();
                $endDate = Carbon::parse($filters['synced_at'][1])->endOfDay();
                $query->whereBetween('quote_sync.synced_at', [$startDate, $endDate]);
            })
            ->when(isset($filters['created_at']), function ($query) use ($filters) {
                $startDate = Carbon::parse($filters['created_at'][0])->startOfDay();
                $endDate = Carbon::parse($filters['created_at'][1])->endOfDay();
                $query->whereBetween('quote_sync.created_at', [$startDate, $endDate]);
            });

        $sortBy = 'quote_sync.id';
        $sortOrder = 'desc';
        if (! empty($filters['sortBy']) && ! empty($filters['sortType'])) {
            $sortBy = $filters['sortBy'];
            $sortOrder = $filters['sortType'] ?? 'desc';
        }

        $count = 0;
        $dataset = $dataset->orderBy($sortBy, $sortOrder)->simplePaginate()->withQueryString();
        $dataset->map(function ($item) {
            $item->quote_type = QuoteTypes::getName($item->quote_type_id)->value ?? '-';
            $item->status_name = QuoteSyncStatus::getName($item->status);

            return $item;
        });

        return [
            'count' => $count,
            'dataset' => $dataset,
        ];
    }

    public function addFollowedEntriesForSyncing(QuoteSync $quoteSync)
    {
        QuoteSync::where('id', '>', $quoteSync->id)
            ->where('quote_uuid', $quoteSync->quote_uuid)
            ->update([
                'is_synced' => false,
                'status' => QuoteSyncStatus::WAITING,
            ]);
    }

    public function addEntriesForReSyncing($status)
    {
        QuoteSync::join(
            DB::raw('(SELECT MIN(id) as min_id, quote_uuid 
                          FROM quote_sync 
                          WHERE is_synced = false 
                          AND status = '.$status.'
                          GROUP BY quote_uuid) as subquery'),
            function ($join) {
                $join->on('quote_sync.quote_uuid', '=', 'subquery.quote_uuid')
                    ->on('quote_sync.id', '>=', 'subquery.min_id');
            }
        )
            ->update([
                'quote_sync.is_synced' => false,
                'quote_sync.status' => QuoteSyncStatus::WAITING,
            ]);
    }
}
