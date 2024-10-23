<?php

namespace App\Services;

use App\Console\Commands\Common\Batchable;
use App\Models\RenewalBatch;

class AddBatchForNonMotors extends BaseService
{
    use Batchable;

    public function handle()
    {
        try {
            $this->logTodayDate();

            $lastBatch = RenewalBatch::whereNull('quote_type_id')->orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                $this->processBatchesFromScratch('2024-07-29');
            } elseif (! $this->isBatchCurrent($lastBatch)) {
                $this->processBatchesFromLastEndDate($lastBatch);
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Batch Number Failed');
            info('message: '.$e->getMessage());
        }
    }
}
