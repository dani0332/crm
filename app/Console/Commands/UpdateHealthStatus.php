<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Jobs\CammyJob;
use App\Models\HealthQuote;
use Illuminate\Console\Command;

class UpdateHealthStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateHealthStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Health leads with follow up status to Lost when last modified date is greater than 30 days';

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
        info('UpdateHealthStatus Command Started');
        HealthQuote::where('quote_status_id', QuoteStatusEnum::FollowedUp)
            ->where('updated_at', '<', date('Y-m-d', strtotime('-29 days')))->chunkById(20, function ($leads) {
                foreach ($leads as $lead) {
                    $lead->update(['quote_status_id' => QuoteStatusEnum::Lost]);
                    CammyJob::dispatch($lead, 'unsub');
                    info('UpdateHealthStatus - Updated Lead Status to Lost & Cammy Unsub Triggered - Ref-ID: '.$lead->uuid.' - Last Modified: '.$lead->updated_at);
                }
            });
        info('UpdateHealthStatus Command Completed');

        return 0;
    }
}
