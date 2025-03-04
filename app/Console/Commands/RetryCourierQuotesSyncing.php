<?php

namespace App\Console\Commands;

use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\EmbeddedTransaction;
use Illuminate\Console\Command;

class RetryCourierQuotesSyncing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes-syncing:retry';

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
        $failedLeads = EmbeddedTransaction::with('quoteRequest')->syncFailed()->get();

        $failedLeads->each(function ($lead) {
            SyncCourierQuoteWithMacrm::dispatch($lead->quoteRequest, $lead->quote_type_id);
        });
    }
}
