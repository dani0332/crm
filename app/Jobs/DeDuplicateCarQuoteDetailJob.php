<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeDuplicateCarQuoteDetailJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $duplicates, protected $tableName) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $isScriptStopped = getAppStorageValueByKey(ApplicationStorageEnums::STOP_DE_DUPLICATION_JOB) == 1;

        if ($isScriptStopped) {
            info('The Script has been Stopped');
        } else {
            info('De-Duplication Job Started');

            $tableName = $this->tableName;

            $allIds = $this->duplicates->pluck('ids')
                ->map(fn ($ids) => explode(',', $ids))
                ->flatten()
                ->unique()
                ->values()
                ->toArray();

            $mainQuery = DB::table($tableName)->whereIn('id', $allIds)->where(function ($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })->get();

            $lastGroupArray = [];

            foreach ($this->duplicates as $group) {
                $groupIDs = explode(',', $group->ids);

                $query = $mainQuery->whereIn('id', $groupIDs);

                $latestUpdatedRecord = $query->sortByDesc('updated_at')->first();

                $lastId = $latestUpdatedRecord->id;
                $lastRecordData = (array) $latestUpdatedRecord;

                $filteredRecords = $query->filter(function ($record) use ($lastId) {
                    return $record->id != $lastId;
                })->values();

                $updatedData = [];

                $recentAdvisorData = $query->sortByDesc('advisor_assigned_date')->first();

                if ($recentAdvisorData && ! empty($recentAdvisorData)) {
                    $updatedData['advisor_assigned_by_id'] = $recentAdvisorData->advisor_assigned_by_id;
                    $updatedData['advisor_assigned_date'] = $recentAdvisorData->advisor_assigned_date;
                }

                foreach ($filteredRecords as $record) {
                    $currentRecord = (array) $record;

                    foreach ($lastRecordData as $column => $lastValue) {
                        if (! in_array($column, ['id', 'created_at', 'updated_at', 'car_quote_request_id', 'advisor_assigned_date', 'advisor_assigned_by_id'])) {
                            if ((empty($lastValue) || is_null($lastValue))
                                && ! empty($currentRecord[$column])
                                && ! is_null($currentRecord[$column])
                                && ! array_key_exists($column, $updatedData)) {

                                $updatedData[$column] = $currentRecord[$column];
                            }
                        }
                    }
                }

                $updatedData = $updatedData + array_diff_key($lastRecordData, $updatedData);

                DB::table($tableName)->where('id', $lastId)->update($updatedData);

                $filteredArray = array_diff($groupIDs, [$lastId]);
                $lastGroupArray[] = implode(',', $filteredArray);
            }

            $deleteAt = collect($lastGroupArray)
                ->map(fn ($ids) => explode(',', $ids))
                ->flatten()
                ->unique()
                ->values()
                ->toArray();

            DB::table($tableName)->whereIn('id', $deleteAt)->update(['is_deleted' => true]);
        }
    }
}
