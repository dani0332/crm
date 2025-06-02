<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class PolicyBulkSendDocuments extends Command
{
    use GenericQueriesAllLobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:bulk-send-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read an array of codes, find PersonalQuote, and dispatch SendBookPolicyDocumentsJob for each.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Define the array of codes to process
        $codes = [
            'HEA-DFEDH47G', 'HEA-M683GHGU', 'HEA-X9HJM5HD', 'HEA-Y5JX37HB', 'HEA-8TZBHZTF',
            'HEA-MXXDCEFX', 'HEA-7F8GXP7S', 'HEA-9SK6W8Q7', 'HEA-SPFSYNCY', 'HEA-MHKSVKJ6',
            'HEA-Y5VPYLSZ', 'HEA-ZEF3H47V', 'HEA-LWPAWGWL', 'HEA-258XRNMT', 'HEA-TT4VF2UF',
            'HEA-DMKHLFPG', 'HEA-V9UPJLXE', 'HEA-HUABPPXK', 'HEA-QT4GWCGR', 'HEA-VL8NZWGF',
            'HEA-F8MYQ4BX', 'HEA-CUPPQ3NM', 'HEA-77AQ6B9Q', 'HEA-DW2G2WMR', 'HEA-8CX9QMT3',
            'HEA-V6WKHP8E', 'HEA-XR4NSCYC', 'HEA-JEL85SRG', 'HEA-ZKGSWLQ5', 'HEA-2CKMM762',
            'HEA-E6XW6Z5H', 'HEA-DPTHTL5Z', 'HEA-HREZ4S35', 'HEA-D4K4H9QZ', 'HEA-95PL8EZF',
            'HEA-9A7599ZU', 'HEA-6YTA8VBP', 'HEA-PEMRLX8F', 'HEA-MWQYXWRW', 'HEA-P5LJTVEM',
            'HEA-QJV8L34W', 'HEA-47QFDZS4', 'HEA-C4GRJ9SG', 'HEA-TPSRFUWD', 'HEA-EABJJTCP',
            'BUS-UFU96QAC',
        ];

        if (empty($codes)) {
            $this->error('No codes provided in the $codes array.');

            return 1;
        }

        $count = 0;
        $notFound = [];

        foreach ($codes as $code) {
            $isProcessEnabled = ApplicationStorage::where('key_name', 'IS_AML_ENTITY_SEARCH_ENABLED')->first();
            if ($isProcessEnabled && $isProcessEnabled->value == 0) {
                LoggerService::info('PolicyBulkSendDocuments - IS_AML_ENTITY_SEARCH_ENABLED is set to Disabled.');

                return 0;
            }

            if (! $code) {
                continue;
            }

            $modelType = null;

            if ($code == 'BUS-UFU96QAC') {
                $quoteObject = BusinessQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Business;
            } else {
                $quoteObject = HealthQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Health;
            }

            if (! $quoteObject) {
                $notFound[] = $code;

                continue;
            }

            $payload = (object) [
                'model_type' => $modelType,
                'quote_id' => $quoteObject->id,
            ];

            SendBookPolicyDocumentsJob::dispatch($payload, $quoteObject->code, true);
            LoggerService::info("PolicyBulkSendDocuments - Dispatched for code: $code (Quote ID: {$quoteObject->code}) for count {$count}");
            $count++;
        }

        LoggerService::info("PolicyBulkSendDocuments - Total dispatched: $count");
        if ($notFound) {
            LoggerService::info('PolicyBulkSendDocuments - codes not found: '.implode(', ', $notFound));
        }

        return 0;
    }
}
