<?php

namespace App\Console\Commands;

use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\QuoteSync;
use App\Traits\PersonalQuoteSyncTrait;
use Illuminate\Console\Command;

class QuoteSyncUpdateCommand extends Command
{
    use PersonalQuoteSyncTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'QuoteSyncUpdate:cron';

    protected $description = 'Sync Quotes Data from QuoteSync table to respective quote tables';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        info('----------- QuoteSyncJob Started -----------');
        $isQuoteSyncEnabled = ApplicationStorage::where('key_name', 'quote_sync_enabled')->first();

        if (! $isQuoteSyncEnabled || $isQuoteSyncEnabled->value == 0) {
            info('----------- QuoteSync is disabled -----------');

            return;
        }

        $entries = QuoteSync::where('is_synced', false)->take(300)->get();

        if ($entries->isEmpty()) {
            info('----------- No entries found to be processed in quote sync table -----------');

            return;
        }

        $uuids = $entries->unique('quote_uuid')->pluck('quote_uuid')->toArray();
        $quotes = PersonalQuote::whereIn('uuid', $uuids)->get()->keyBy(function (PersonalQuote $item, int $key) {
            return $item->uuid.'_'.$item->quote_type_id;
        })->all();

        foreach ($entries as $entry) {

            info('Syncing entry: '.$entry->quote_uuid);

            $key = $entry->quote_uuid.'_'.$entry->quote_type_id;
            if (! empty($quotes[$key])) {
                // Existing quote
                $this->processExistingQuote($quotes[$key], $entry);
            } else {
                // Quote not found
                $quotes[$key] = $this->processQuoteNotFound($entry);
            }
        }
    }
}
