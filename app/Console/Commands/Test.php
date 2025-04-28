<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Jobs\Audit\LogAllocation;
use App\Models\Audit\AllocationAudit;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

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
        // $audit = AllocationAudit::first();

        // dd(
        //     $audit
        // );

        // Auth::loginUsingId(1108);

        // $personalQuote = PersonalQuote::find(232537);

        // LogAllocation::dispatch($personalQuote);

        // AllocationAudit::log($personalQuote);

        $lead = TravelQuote::find(15108);

        LogAllocation::dispatch($lead, QuoteTypes::TRAVEL);
    }
}
