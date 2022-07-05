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
        Log::info('Over All Insly Job Started');

        $JobSwitch = ApplicationStorage::where([['key_name', 'INSLY_MIGRATION_SCHEDULER_SWITCH'], ['value', 1], ['is_active', 1]])->get()->first();

        if ($JobSwitch != '') {
            date_default_timezone_set(config('app.timezone'));

            $format = 'd.m.Y';

            $numberOfDays = Config::get('constants.INSLY_DAYS_TO_CAPTURE');

            Log::info('number of days' . $numberOfDays);

            $defaultStartDate = (string)Config::get('constants.INSLY_DEFAULT_DATE_TO_CAPTURE');

            Log::info('default start date : ' . $defaultStartDate);

            $sortedDate = Carbon::createFromFormat($format, $defaultStartDate)->format($format);

            Log::info('Parsed Default Date : ' . $sortedDate);

            $lastBatch = InslyDataService::GetLastInslyBatchLog();

            Log::info('Fetched records');

            Log::info($lastBatch);

            if ($lastBatch == "") {
                Log::info('Record not found');
                $nextStartDate = $sortedDate;

                Log::info('Next Start Date : ' . $nextStartDate);
                $nextEndDate = date($format, strtotime($nextStartDate . ' + ' . $numberOfDays . ' days'));

                Log::info('Next End Date : ' . $nextEndDate);
            } else {
                Log::info('Record found');
                $nextStartDate = date($format, strtotime($lastBatch->batch_end_date . ' + 1 days'));

                Log::info('Next Start Date : ' . $nextStartDate);
                $nextEndDate = date($format, strtotime($nextStartDate . ' + ' . $numberOfDays . ' days'));

                Log::info('Next End Date : ' . $nextEndDate);
            }
            Log::info('Process FetchAndProcessInslyData trigged');

            $data = InslyDataService::GetDataFromInsly($nextStartDate, $nextEndDate);

            $dataCount = count(json_decode($data)->policies);

            Log::info('Data from url fetched with number of records  : ' . $dataCount);

            InslyDataService::AddInslyBatchLog($nextStartDate, $nextEndDate, $dataCount);

            Log::info('Record added in db for batch log for start date : ' . $nextStartDate . ', end date : ' . $nextEndDate . ', count : ' . $dataCount);

            dispatch(new InslyDataProcessingJob($data));
        } else {
            Log::info('Ending Job as Application Storage Switch is not ON');
        }
    }
}
