<?php

namespace App\Console\Commands;

use App\Enums\QuoteSyncStatus;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\QuoteSync;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $entries = QuoteSync::where('is_synced', false)
            ->where('status', QuoteSyncStatus::WAITING)
            ->take(300)
            ->get();

        if ($entries->isEmpty()) {
            info('----------- No entries found to be processed in quote sync table -----------');

            return;
        }

        // mark entries in progress
        QuoteSync::whereIn('id', $entries->pluck('id')->toArray())
            ->update(['status' => QuoteSyncStatus::INPROGRESS]);

        $uuids = $entries->unique('quote_uuid')->pluck('quote_uuid')->toArray();
        $quotes = PersonalQuote::whereIn('uuid', $uuids)->get()->keyBy(function (PersonalQuote $item, int $key) {
            return $item->uuid.'_'.$item->quote_type_id;
        })->all();

        foreach ($entries as $entry) {

            try {
                DB::beginTransaction();

                info('Syncing entry: ' . $entry->quote_uuid . ' - ' . $entry->id);
                $key = $entry->quote_uuid.'_'.$entry->quote_type_id;
                if (! empty($quotes[$key])) {
                    // Existing quote
                    $this->processExistingQuote($quotes[$key], $entry);
                } else {
                    // Quote not found
                    $quotes[$key] = $this->processQuoteNotFound($entry);
                }

                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();

                $error = 'QuoteSyncJob Error syncing entry: '.$entry->quote_uuid . ' - ' . $entry->id .' - '.$e->getMessage();
                info($error.' --- '.$e->getTraceAsString());
                QuoteSync::where('id', $entry->id)->update(['status' => QuoteSyncStatus::FAILED, 'error' => $error]);
            }
        }

        info('----------- QuoteSyncJob Completed -----------');
    }
}
