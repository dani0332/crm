<?php

namespace App\Console\Commands;

use App\Enums\EnvEnum;
use App\Enums\QuoteSyncStatus;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\QuoteSync;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
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
    private $startId = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (empty($this->schemas)) {
            $this->cacheSchemas();
        }

        if (config('constants.APP_ENV') == EnvEnum::PRODUCTION) {
            $this->startId = 4500000;
        }

        info('----------- QuoteSyncJob Started -----------');
        $isQuoteSyncEnabled = ApplicationStorage::where('key_name', 'quote_sync_enabled')->first();

        if (! $isQuoteSyncEnabled || $isQuoteSyncEnabled->value == 0) {
            info('----------- QuoteSync is disabled -----------');

            return;
        }

        try {

            $beforeTime = Carbon::now()->setTimezone('Asia/Dubai')->subMinutes(15);
            $quoteUuids = QuoteSync::where('is_synced', false)
                ->whereIn('status', [QuoteSyncStatus::INPROGRESS, QuoteSyncStatus::FAILED])
                ->where('id', '>', $this->startId)
                ->get()
                ->filter(function ($entry) use ($beforeTime) {
                    return Carbon::parse($entry->updated_at)->diffInMinutes($beforeTime, false) >= 0;
                })->unique('quote_uuid')->pluck('quote_uuid')->toArray();

            if (count($quoteUuids) > 0) {
                $quoteUuids = "'".implode("','", $quoteUuids)."'";

                DB::table('quote_sync as qs')
                    ->join(DB::raw("(
                            SELECT 
                                MIN(CASE WHEN is_synced = false THEN id END) AS min_id,
                                quote_uuid
                            FROM quote_sync
                            WHERE quote_uuid IN ({$quoteUuids})
                            AND id > ".intval($this->startId).'
                            AND status IN ('.QuoteSyncStatus::INPROGRESS.', '.QuoteSyncStatus::FAILED.')
                            GROUP BY quote_uuid
                        ) as subquery'), function ($join) {
                        $join->on('qs.id', '>=', 'subquery.min_id')
                            ->on('qs.quote_uuid', '=', 'subquery.quote_uuid');
                    })
                    ->update([
                        'qs.is_synced' => false,
                        'qs.status' => QuoteSyncStatus::WAITING,
                    ]);
            }

        } catch (Exception $e) {
            $error = 'QuoteSyncJob Error re-queing failed or stuck entries: '.$quoteUuids.' - '.$e->getMessage();
            info($error.' --- '.$e->getTraceAsString());
        }

        $entries = QuoteSync::where('is_synced', false)
            ->where('status', QuoteSyncStatus::WAITING)
            ->where('id', '>', $this->startId)
            ->take(2000)
            ->get();

        if ($entries->isEmpty()) {
            // info('----------- No entries found to be processed in quote sync table -----------');
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
                info('Syncing entry: '.$entry->quote_uuid.' - '.$entry->id);
                if ($entry->updated_fields === '{"is_cold":true}') {
                    QuoteSync::where('id', $entry->id)->update(['is_synced' => true, 'status' => QuoteSyncStatus::COMPLETED, 'synced_at' => now()]);
                } else {
                    $key = $entry->quote_uuid.'_'.$entry->quote_type_id;
                    if (! empty($quotes[$key])) {
                        // Existing quote
                        $this->processExistingQuote($quotes[$key], $entry);
                    } else {
                        // Quote not found
                        $quotes[$key] = $this->processQuoteNotFound($entry);
                    }
                }
                info('Syncing entry complete: '.$entry->quote_uuid.' - '.$entry->id);
            } catch (Exception $e) {
                $error = 'QuoteSyncJob Error syncing entry: '.$entry->quote_uuid.' - '.$entry->id.' - '.$e->getMessage();
                info($error.' --- '.$e->getTraceAsString());
                QuoteSync::where('id', $entry->id)->update(['status' => QuoteSyncStatus::FAILED, 'error' => $error]);
            }
        }
    }
}
