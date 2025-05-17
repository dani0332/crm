<?php

namespace App\Console\Commands;

use App\Models\TravelQuote;
use App\Enums\LeadSourceEnum;
use Illuminate\Console\Command;
use App\Services\TravelRenewalService;

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

        app(TravelRenewalService::class)->createTravelRenewalLeads($quotes);
    }
}
