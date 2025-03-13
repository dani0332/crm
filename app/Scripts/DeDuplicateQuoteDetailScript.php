<?php

namespace App\Scripts;

use App\Enums\ApplicationStorageEnums;
use Exception;
use Illuminate\Support\Facades\DB;

class DeDuplicateQuoteDetailScript
{
    public static function run()
    {
        $limit = request('limit', 10);
        $tableName = request('tableName', 'car_quote_request_detail_duplicate1');

        try {
            $isScriptStopped = getAppStorageValueByKey(ApplicationStorageEnums::STOP_DE_DUPLICATION_JOB) == 1;

            if ($isScriptStopped) {
                info('The Script has been Stopped');

                return 'Script stopped by configuration';
            }

            info(self::class.' - De-Duplication Job Started');

            $duplicates = DB::table($tableName)
                ->select('car_quote_request_id', DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
                ->groupBy('car_quote_request_id')
                ->havingRaw('COUNT(car_quote_request_id) > 1')
                ->where('is_deleted', 0)
                ->orderBy('car_quote_request_id')
                ->limit($limit)
                ->get();

            if ($duplicates->isEmpty()) {
                info(self::class.' - No duplicates found to process');

                return response()->json(['success' => true, 'message' => 'No duplicates found to process']);
            }

            $allIds = $duplicates->pluck('ids')
                ->map(fn ($ids) => explode(',', $ids))
                ->flatten()
                ->unique()
                ->values()
                ->toArray();

            $mainQuery = DB::table($tableName)
                ->whereIn('id', $allIds)
                ->where(function ($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->get();

            $lastGroupArray = [];

            foreach ($duplicates as $group) {
                try {
                    $groupIDs = explode(',', $group->ids);
                    $query = $mainQuery->whereIn('id', $groupIDs);
                    $latestUpdatedRecord = $query->sortByDesc('updated_at')->first();

                    if (! $latestUpdatedRecord) {
                        info(self::class.' - No latest updated record found for group: '.$group->ids);

                        continue;
                    }

                    $lastId = $latestUpdatedRecord->id;

                    info(self::class.' - Processing Group: '.$group->ids.' - Last ID: '.$lastId);

                    $lastRecordData = (array) $latestUpdatedRecord;
                    $filteredRecords = $query->filter(fn ($record) => $record->id != $lastId)->values();

                    $updatedData = [];
                    $recentAdvisorData = $query->sortByDesc('advisor_assigned_date')->first();

                    if ($recentAdvisorData && ! empty($recentAdvisorData)) {
                        $updatedData['advisor_assigned_by_id'] = $recentAdvisorData->advisor_assigned_by_id;
                        $updatedData['advisor_assigned_date'] = $recentAdvisorData->advisor_assigned_date;
                    }

                    foreach ($filteredRecords as $record) {
                        $currentRecord = (array) $record;
                        foreach ($lastRecordData as $column => $lastValue) {
                            if (! in_array($column, ['id', 'created_at', 'updated_at', 'car_quote_request_id', 'advisor_assigned_date', 'advisor_assigned_by_id']) &&
                                (empty($lastValue) || is_null($lastValue)) &&
                                ! empty($currentRecord[$column]) &&
                                ! is_null($currentRecord[$column]) &&
                                ! array_key_exists($column, $updatedData)) {
                                $updatedData[$column] = $currentRecord[$column];
                            }
                        }
                    }

                    $updatedData = $updatedData + array_diff_key($lastRecordData, $updatedData);
                    DB::table($tableName)->where('id', $lastId)->update($updatedData);

                    $filteredArray = array_diff($groupIDs, [$lastId]);
                    $lastGroupArray[] = implode(',', $filteredArray);

                    info(self::class.' - Group: '.$group->ids.' - Updated Record: '.$lastId);
                } catch (Exception $e) {
                    info(self::class.' - Error processing group '.$group->ids.': '.$e->getMessage());
                }
            }

            try {
                $deleteAt = collect($lastGroupArray)
                    ->map(fn ($ids) => explode(',', $ids))
                    ->flatten()
                    ->unique()
                    ->values()
                    ->toArray();

                if (! empty($deleteAt)) {
                    DB::table($tableName)->whereIn('id', $deleteAt)->update(['is_deleted' => true]);
                }
            } catch (Exception $e) {
                info(self::class.' - Error deleting records: '.$e->getMessage());

                return response()->json(['success' => false, 'message' => 'Error deleting records: '.$e->getMessage()]);
            }

            info(self::class.' - De-Duplication Job Completed');

            return 'De-duplication completed successfully';

        } catch (Exception $e) {
            info(self::class.' - Fatal error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Fatal error: '.$e->getMessage()]);
        }
    }
}
