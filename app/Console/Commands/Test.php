<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
use App\Models\TravelQuote;
use App\Services\TravelRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $quotes = TravelQuote::where('source', LeadSourceEnum::RENEWAL_UPLOAD)->limit(5)->latest()->get();

        foreach ($quotes as $quote) {
            $quote->advisor_id = null;
            $quote->assignment_type = null;
            $quote->quote_batch_id = null;
            $quote->saveQuietly();

            app(TravelRenewalService::class)->dispatchLeadAllocationJob($quote->uuid);

            Sleep::for(10)->second();
        }
    }
}
