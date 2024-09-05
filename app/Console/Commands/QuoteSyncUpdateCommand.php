<?php

namespace App\Console\Commands;

use App\Enums\QuoteSyncStatus;
use App\Models\ApplicationStorage;
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

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->init();

        info('----------- QuoteSyncJob Started -----------');
        $isQuoteSyncEnabled = ApplicationStorage::where('key_name', 'quote_sync_enabled')->first();

        if (! $isQuoteSyncEnabled || $isQuoteSyncEnabled->value == 0) {
            info('----------- QuoteSync is disabled -----------');

            return;
        }

        $this->reQueueFailedOrStuck();

        $entries = QuoteSync::where('is_synced', false)
            ->where('status', QuoteSyncStatus::WAITING)
            ->where('id', '>', $this->startId)
            ->take(2000)
            ->get();

        $this->processQuoteSyncEntries($entries);
    }

    private function reQueueFailedOrStuck()
    {
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
    }
}
