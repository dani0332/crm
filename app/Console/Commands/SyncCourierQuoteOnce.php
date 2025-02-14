<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Services\MACRMService;
use Illuminate\Console\Command;

class SyncCourierQuoteOnce extends Command
{
    protected $signature = 'sync:courier-quotes-once';
    protected $description = 'Sync courier quotes with MACRM for a list of RefIds, running only once on deployment.';
    protected $refIds = [
        '7KTQPPVP',
        'SWAFXD7Z',
        '9D7HURXZ',
        '9USRQ2NF',
        'Q9DBFF3N',
        'JEVDYP8B',
        'NSL3E6BJ',
        'WAAX6SWX',
        '537T6EJQ',
        '8RRJUVWP',
        'N5XBM6HQ',
        '28XY9MVC',
        '8BLRYV35',
        'A78UMBUF',
        'D8BPW4RW',
        '36QGJL6H',
        'GDK5CNAM',
    ];

    public function handle()
    {
        if (getAppStorageValueByKey(ApplicationStorageEnums::COURIER_QUOTES_SYNC_ENABLED) !== '1') {
            $this->info('SyncCourierQuoteOnce - Courier quotes syncing is disabled.');

            return;
        }

        $this->info('SyncCourierQuoteOnce - Syncing process started at: '.now());
        foreach ($this->refIds as $refId) {
            $quote = CarQuote::where('uuid', $refId)->first();
            if (! $quote) {
                $this->info("SyncCourierQuoteOnce - Quote not found for RefId: {$refId}");

                continue;
            }
            $quoteTypeId = QuoteTypeId::Car;
            $result = MACRMService::syncCourierQuote($quote, $quoteTypeId);

            $result
                ? $this->info("SyncCourierQuoteOnce - Successfully synced quote for RefId: {$refId}")
                : $this->warn("SyncCourierQuoteOnce - Failed to sync quote for RefId: {$refId}");

            // Add a 1-second delay before moving to the next RefId
            sleep(1);
        }
    }
}
