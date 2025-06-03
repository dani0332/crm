<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\DocumentType;
use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
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

        LoggerService::info("PolicyBulkSendDocuments Started");
        // Define the array of codes to process
        $codes = [
            'HEA-X9HJM5HD', 'HEA-MXXDCEFX', 'HEA-7F8GXP7S', 'HEA-VJ72TGS8', 'HEA-9SK6W8Q7',
            'HEA-MHKSVKJ6', 'HEA-ZEF3H47V', 'HEA-LWPAWGWL', 'HEA-258XRNMT', 'HEA-TT4VF2UF',
            'HEA-VL8NZWGF', 'HEA-77AQ6B9Q', 'HEA-DW2G2WMR', 'HEA-V6WKHP8E', 'HEA-XR4NSCYC',
            'HEA-JEL85SRG', 'HEA-ZKGSWLQ5', 'HEA-2CKMM762', 'HEA-E6XW6Z5H', 'HEA-DPTHTL5Z',
            'HEA-HREZ4S35', 'HEA-D4K4H9QZ', 'HEA-95PL8EZF', 'HEA-6YTA8VBP', 'HEA-PEMRLX8F',
            'HEA-MWQYXWRW', 'HEA-P5LJTVEM', 'HEA-47QFDZS4', 'HEA-EABJJTCP', 'HEA-8TZBHZTF',
            'BUS-UFU96QAC'
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
                LoggerService::error('PolicyBulkSendDocuments - Code is not found.');
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
                LoggerService::error('PolicyBulkSendDocuments - Code is not found.');
                continue;
            }

            foreach ($quoteObject->documents as $document) {
                LoggerService::info("PolicyBulkSendDocuments - Water mark document: {$document->id} for code: {$code}");
                $documentType = DocumentType::where('code', $document->document_type_code)->first();
                $isWaterMarkQualifyDoc = app(QuoteDocumentService::class)->getWatermarkProperty($quoteObject, $documentType);

                if ($quoteObject && $isWaterMarkQualifyDoc && $documentType) {
                    WatermarkDocumentsJob::dispatchSync(
                        $document->id, $quoteObject->uuid, $documentType->id
                    );
                }
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
