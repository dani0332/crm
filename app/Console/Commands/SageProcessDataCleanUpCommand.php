<?php

namespace App\Console\Commands;

use App\Enums\SageEnum;
use App\Models\SageProcess;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class SageProcessDataCleanUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-process:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete completed sage process which are created more than 7 days ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = Carbon::now()->subDays(7);

        SageProcess::where('status', SageEnum::SAGE_PROCESS_COMPLETED_STATUS)
            ->where('created_at', '<', $date)
            ->delete();

        LoggerService::info('Saga Process Data Clean Up Command executed successfully.', extra: [' data before date' => $date]);

    }
}
