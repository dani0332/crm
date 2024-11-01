<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Services\MACRMService;
use Illuminate\Console\Command;

class SyncCourierQuotesOnce extends Command
{
    protected $signature = 'sync:courier-quotes-once';
    protected $description = 'Sync courier quotes with MACRM for a list of RefIds, running only once on deployment.';
    protected $refIds = [
        '33LMCP2Q',
        'ZXEFPZXX',
        'RTX7HD9Y',
        'L773J3GF',
        '7EX8VHXB',
        'KYA6GKKN',
        'KJ46NCGC',
        'MUTQMU82',
        'KK2ZH3B5',
        'RYZWJYCQ',
        'TF8HKWLT',
        '65MF8TB8',
        'WTZ7N6NF',
        'S3YP5QJT',
        '9EA7S3H8',
        'SGE8RJWP',
        'VCX6NBUT',
        'APKTR9XQ',
        'LV5UX3FL',
        'XNAPEPCT',
        'NVUH77XW',
        '4YQU4TNT',
        'SGB4RWVB',
        'JSGZVQF8',
        'T8NQNAH5',
        'W9D66QQA',
        '7KXY7LDD',
        'EQ3CF8R9',
        'EC24MCKC',
        'X3HSASDC',
        'LMRCG6X8',
        'WCV47QTS',
        'ETGZMAQN',
        '67KZCGQQ',
        'MPRN8XEL',
        '7NQXKN4E',
        'Y9J7B2CB',
        'HNX7KW5H',
        '7YZ662XN',
        'XG9QY857',
        'QEMKJTXS',
        '4VH59ZBR',
        'QXK9GLRP',
        'GMRMFXMC',
        'TLZZNCWB',
        '6QGRQ3BZ',
        'BDDFB94H',
        'RS2APZJK',
        '6C4XJP2Q',
        'LL7HG2YD',
        'BR37BFYU',
        '62FFX7UD',
        'HKGPRA4Q',
        '3LG2VVWG',
        'PHU4KZGM',
        'YHVJY3H7',
        '73RSXUHE',
        'N3YM4S5P',
        '6DSPYFT4',
        'K2AURYAE',
        'CXTEWNRK',
        'XXMZBCV3',
        '83XZKF4A',
        'NZ4PCXPB',
        '75VR45KS',
        'X2N8A6NY',
        'X26C35YN',
        'ZZHLC956',
        '524SUPMM',
        'TPH56VGR',
        'BEHSKKKQ',
        '5R4Y475M',
        '7B8HS8E4',
        '55KB3GXA',
        'W2PJQY8G',
        '56VTU3FA',
        '9K97929B',
        'TRRDKCZC',
        'UUC4SEYJ',
        'FL6WWPNP',
        '9U3FSQ9R',
        'Y4QQAYLS',
        'SXB46CTR',
        'Z3BXACSE',
        'T5DASCD2',
        'PS44LU54',
        'TARQTKY5',
        'XAQLEWP2',
        '2ZEFFABT',
        '5SS5ZT8H',
        '8SVVKN6V',
        '35J3RH2N',
        '8PBWUCD5',
        'YG4HYBAG',
        'D5FUBUTJ',
        'JK7SSNKL',
        '79Y5Q8LP',
        'JH28HGJR',
        'ZTWLXBHF',
        'VBYTTULV',
        '6KE59HFX',
        'KBNKBV6F',
        '2U7E965P',
        'GHCNWNVT',
        '5MHJW6R7',
        '2VFLEC7J',
        'N32MNAKU',
        'GDDZS5CA',
        'C8MMSNUX',
        '2KFVCJT9',
        'X26U9YAZ',
        'HUL8HWY8',
        '86GR5EDH',
        'SBU59E7W',
        '3EBFGNLT',
        'VBZ34BC2',
        'QY2AK6LA',
    ];

    public function handle()
    {
        if (getAppStorageValueByKey(ApplicationStorageEnums::COURIER_QUOTES_SYNC_ENABLED) !== "1") {
            $this->info('Courier quotes syncing is disabled.');

            return;
        }

        $this->info('Syncing process started at: '.now());
        foreach ($this->refIds as $refId) {
            $quote = CarQuote::where('uuid', $refId)->first();
            if (! $quote) {
                $this->info("Quote not found for RefId: {$refId}");

                continue;
            }
            $quoteTypeId = QuoteTypeId::Car;
            $result = MACRMService::syncCourierQuote($quote, $quoteTypeId);

            $result
                ? $this->info("Successfully synced quote for RefId: {$refId}")
                : $this->warn("Failed to sync quote for RefId: {$refId}");

            // Add a 1-second delay before moving to the next RefId
            sleep(1);
        }

        $this->info('Syncing process completed successfully at: '.now());
    }
}
