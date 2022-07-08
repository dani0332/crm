<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InslyDataService;
use App\Jobs\InslyDataProcessingJob;
use App\Models\ApplicationStorage;
use Config;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InslyOldDataCapture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'InslyOldDataCapture:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will run daily in week days after working hours to capture old data from insly and add in CDB';

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
        Log::channel('daily')->info('Over All Insly Job Started');

        $JobSwitch = ApplicationStorage::where([['key_name', 'INSLY_MIGRATION_SCHEDULER_SWITCH'],['value', 1], ['is_active', 1]])->get()->first();

        if($JobSwitch != ''){
            date_default_timezone_set(config('app.timezone'));

            $format = 'd.m.Y';

            $numberOfDays = Config::get('constants.INSLY_DAYS_TO_CAPTURE');

            Log::channel('daily')->info('number of days'. $numberOfDays);

            $defaultStartDate = (string)Config::get('constants.INSLY_DEFAULT_DATE_TO_CAPTURE');

            Log::channel('daily')->info('default start date : '.$defaultStartDate);

            $sortedDate = Carbon::createFromFormat($format, $defaultStartDate)->format($format);

            Log::channel('daily')->info('Parsed Default Date : '.$sortedDate);

            $lastBatch = InslyDataService::GetLastInslyBatchLog();

            Log::channel('daily')->info('Fetched records');

            Log::channel('daily')->info($lastBatch);

            if($lastBatch == ""){
                Log::channel('daily')->info('Record not found');
                $nextStartDate = $sortedDate;

                Log::channel('daily')->info('Next Start Date : '. $nextStartDate);
                $nextEndDate = date($format, strtotime($nextStartDate. ' + '.$numberOfDays.' days'));

                Log::channel('daily')->info('Next End Date : '. $nextEndDate);
            }else {
                Log::channel('daily')->info('Record found');
                $nextStartDate = date($format, strtotime($lastBatch->batch_end_date. ' + 1 days'));

                Log::channel('daily')->info('Next Start Date : '. $nextStartDate);
                $nextEndDate = date($format, strtotime($nextStartDate. ' + '.$numberOfDays.' days'));

                Log::channel('daily')->info('Next End Date : '. $nextEndDate);
            }
            Log::channel('daily')->info('Process FetchAndProcessInslyData trigged');

            $data = InslyDataService::GetDataFromInsly($nextStartDate, $nextEndDate);

            $dataCount = count(json_decode($data)->policies);

            Log::channel('daily')->info('Data from url fetched with number of records  : '.$dataCount);

            InslyDataService::AddInslyBatchLog($nextStartDate, $nextEndDate, $dataCount);

            Log::channel('daily')->info('Record added in db for batch log for start date : '.$nextStartDate.', end date : '.$nextEndDate.', count : '. $dataCount);

            dispatch(new InslyDataProcessingJob($data));
        }
        else{
            Log::channel('daily')->info('Ending Job as Application Storage Switch is not ON');
        }
    }
}
