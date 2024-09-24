<?php

namespace App\Console\Commands;

use App\Models\RenewalBatch;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddBatchNumberNonMotors extends Command
{
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
            info('today date for batch job is : '.json_encode(now()->toDateString()));
            $lastBatch = RenewalBatch::whereNull('quote_type_id')->orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                info('inside creating batches from scratch');
                $batches = $this->generateBatchNumbers(Carbon::parse('2024-09-01'));
                if (count($batches) > 0) {
                    foreach ($batches as $batch) {
                        $this->insertQuoteBatch($batch);
                    }
                    info('batches created');
                }
            } elseif (! (now()->startOfDay() >= Carbon::parse($lastBatch->start_date)->startOfDay() && now()->endOfDay() <= Carbon::parse($lastBatch->end_date)->endOfDay())) {
                info('inside creating batch of current week');
                $batches = $this->generateBatchNumbers(Carbon::parse($lastBatch->end_date)->addDays(1));
                if (count($batches) > 0) {
                    foreach ($batches as $batch) {
                        $this->insertQuoteBatch($batch);
                    }
                    info('batches created');
                }
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Batch Number Failed');
            info('message: '.$e->getMessage());
        }
    }

    private function insertQuoteBatch($batch)
    {
        RenewalBatch::insert([
            'name' => $batch['name'],
            'start_date' => $batch['startDate'],
            'end_date' => $batch['endDate'],
            'month' => $batch['month'],
            'year' => $batch['year'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateBatchNumbers($startDate)
    {
        $batchArray = [];

        while ($startDate < now()) {
            $currentDate = $startDate->copy();
            $nextWeek = $startDate->copy()->addDays(6);

            $month = $currentDate->format('M');
            $year = $currentDate->format('y');
            $monthNumber = $currentDate->format('m');
            $fullYear = $currentDate->format('Y');

            $batchArray[] = [
                'name' => $month.'-'.$year,
                'startDate' => $currentDate->toDateString(),
                'endDate' => $nextWeek->toDateString(),
                'month' => $monthNumber,
                'year' => $fullYear,
            ];

            $startDate->addWeek();
        }

        return $batchArray;
    }
}
