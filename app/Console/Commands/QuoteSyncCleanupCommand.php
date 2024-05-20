<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\QuoteSync;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QuoteSyncCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'QuoteSyncCleanup:cron';

    protected $description = 'QuoteSync table clean up for old data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        info('QuoteSyncJob cleanup Started');
        $isQuoteSyncCleanupEnabled = ApplicationStorage::where('key_name', ApplicationStorageEnums::QUOTE_SYNC_CLEANUP_ENABLED)->first();

        if (! $isQuoteSyncCleanupEnabled || $isQuoteSyncCleanupEnabled->value == 0) {
            info('QuoteSyncJob cleanup is disabled');

            return;
        }

        $cleanupDays = ApplicationStorage::where('key_name', ApplicationStorageEnums::QUOTE_SYNC_CLEANUP_DAYS)->first();
        $cleanupDate = Carbon::now()->subDays($cleanupDays->value);

        DB::transaction(function () use ($cleanupDate) {
            $deletedEntries = QuoteSync::where('created_at', '<', $cleanupDate)
                ->where('is_synced', true)
                ->take(3000)
                ->delete();

            info('QuoteSyncJob cleanup date: '.$cleanupDate->toDateTimeString().' - '.$deletedEntries.' entries deleted from quote sync table');
        });
    }
}
