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
        'UV3G62LC',
        'QT6XCR74',
        'EA2X7PRG',
        '6TZ6QXCU',
        'FX32XXL6',
        '7KTQPPVP',
        'SWAFXD7Z',
        'K46EKAUH',
        '9D7HURXZ',
        'EFLU8NQC',
        'YZA6T9C2',
        '9USRQ2NF',
        'HY6CT5W8',
        'Q9DBFF3N',
        '8R4ZVN6Y',
        'KBMEYUGF',
        '7BNPGAQB',
        'JEVDYP8B',
        'Y2YXNMJG',
        'NSL3E6BJ',
        'YTUS2SKZ',
        'GAVJRMLU',
        'H88U5C97',
        'JU7N8KPE',
        'WAAX6SWX',
        '36HHZ7EG',
        'ACH7BL73',
        'CE9RQDQT',
        'ZA9HVW3W',
        '537T6EJQ',
        '7ZGXPGBG',
        'SQ4D8GUU',
        'KW8HG6CG',
        'SYQGZQPV',
        'LJUJE6HM',
        'ZKENRTMX',
        'FUJWFJDQ',
        'L9KWB7E8',
        'ETG2L4SX',
        'YBUHUFVK',
        '5LL24AEL',
        'X7K7MVLT',
        'G2YCUJFP',
        '8RRJUVWP',
        'N5XBM6HQ',
        'XGZV6YEB',
        '28XY9MVC',
        '6BBB86GB',
        'CJWZ799X',
        '77863U6D',
        'FY7ETS5C',
        '8BLRYV35',
        'UEF53K5V',
        'QGU9MZLZ',
        'GXX7SPDD',
        'HR3W7P8D',
        'E2JAMSYV',
        '6975LMEC',
        '8WAY62LW',
        'A78UMBUF',
        '97NPRMWZ',
        'ZZPC9DZ8',
        '5HAQ6GB9',
        'JSFT6FKS',
        '5K9DJGU9',
        'HXHX3ALR',
        'D8BPW4RW',
        'CEKWXQ9B',
        '36QGJL6H',
        'MYCRW2CV',
        'GDK5CNAM',
        'Z8YDTECT',
        '3ZUR6BJQ',
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
