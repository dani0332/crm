<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\CarQuote;
use App\Models\DttRevival;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DttFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dtt:followup';

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
        try {
            $unreplied = DttRevival::where([
                ['reply_received', 0],
            ])->get();
            $today = Carbon::today();
            foreach ($unreplied as $item) {

                $lead = CarQuote::where('uuid', $item->uuid)->first();


                // $dateLimitForAdvisor = Carbon::parse($created_at)->addDays(6);
            }
        } catch (\Exception $exception) {
            info('DTT Exception : ' . $exception->getMessage());
        }
    }
}
