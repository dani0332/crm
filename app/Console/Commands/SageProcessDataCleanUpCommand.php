<?php

namespace App\Console\Commands;

use App\Enums\SageEnum;
use App\Models\SageProcess;
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

        info('Saga Process Data Clean Up Command executed successfully.', [' data before date' => $date]);

        // TODO : Check if this is required or not
        /*
         DB::statement('OPTIMIZE TABLE sage_processes');
        info('Sage Process table optimized successfully.');
        */

    }
}
