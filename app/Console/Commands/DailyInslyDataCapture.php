<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\InslyDataService;
use App\Jobs\InslyDataProcessingJob;
use Config;


class DailyInslyDataCapture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inslyDataCaputre:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will run daily in week days after working hours to capture yesterday data from insly and add in CDB';

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
        Log::info('Process FetchAndProcessInslyData trigged');
        date_default_timezone_set(config('app.timezone'));
        $format = Config::get('constants.datetime_format');
        $endDate = date($format);
        $startDate = date($format, strtotime($endDate . ' - 1 days'));
        $data = InslyDataService::GetDataFromInsly($startDate, $endDate);
        dispatch(new InslyDataProcessingJob($data));
    }
}
