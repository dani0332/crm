<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypeId;
use App\Jobs\MACRM\CancelCourierQuoteOnMACRM;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\CarQuote;
use Illuminate\Console\Command;

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
        $lead = CarQuote::find(171967);
        // SyncCourierQuoteWithMacrm::dispatch($lead, QuoteTypeId::Car);
        CancelCourierQuoteOnMACRM::dispatch($lead, QuoteTypeId::Car);
    }
}
