<?php

namespace App\Console\Commands;

use App\Console\Commands\Common\Batchable;
use App\Models\RenewalBatch;
use Illuminate\Console\Command;

class AddBatchNumberNonMotors extends Command
{
    use Batchable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddBatchNumberNonMotors:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create batch numbers for non-motors';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        config(['database.default' => 'mysql']);

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $type = 'non motor';
            $this->logTodayDate($type);
            $lastBatch = RenewalBatch::whereNull('quote_type_id')->orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                $this->processBatchesFromScratch('2024-07-29', $type);
            } elseif (! $this->isBatchCurrent($lastBatch)) {
                $this->processBatchesFromLastEndDate(type: $type);
            } else {
                info('non motor batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Non Motor Batch Number Failed');
            info('message: '.$e->getMessage());
        }
    }
}
