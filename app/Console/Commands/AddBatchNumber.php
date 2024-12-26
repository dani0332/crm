<?php

namespace App\Console\Commands;

use App\Models\QuoteBatches;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddBatchNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddBatchNumber:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        Log::info('Add Batch Command Started');
        try {
            $lastBatch = QuoteBatches::orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                info('inside creating batches from scratch');
                $batches = $this->generateBatchNumbers(Carbon::parse('2018-08-06'));
                $this->createBatches($batches);
            } elseif (! (now()->startOfDay() >= Carbon::parse($lastBatch->start_date)->startOfDay() && now()->endOfDay() <= Carbon::parse($lastBatch->end_date)->endOfDay())) {
                info('inside creating batch of current week');
                $batches = $this->generateBatchNumbers(Carbon::parse($lastBatch->end_date)->addDays(1));
                $this->createBatches($batches);
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Batch Number Job Failed');
            info('message: '.$e->getMessage());
        }
    }

    private function createBatches($batches)
    {
        if (count($batches) > 0) {
            foreach ($batches as $batch) {
                $this->insertQuoteBatch($batch);
            }
            info('batches created');
        }
    }

    private function insertQuoteBatch($batch)
    {
        QuoteBatches::insert([
            'name' => explode('|', $batch)[1],
            'start_date' => explode(',', $batch)[0],
            'end_date' => explode('|', explode(',', $batch)[1])[0],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateBatchNumbers($startDate)
    {
        $batchArray = [];
        $count = QuoteBatches::count() + 1;
        while ($startDate < now()) {
            $currentDate = $startDate->toDateString();
            $nextWeek = $startDate->addDays(6)->toDateString();
            $key = $currentDate.','.$nextWeek;
            $value = 'Batch '.$count;
            array_push($batchArray, $key.'|'.$value);
            $count++;
        }

        return $batchArray;
    }
}
