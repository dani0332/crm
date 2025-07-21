<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Services\ActivitiesService;
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
        LoggerService::info('PolicyBulkSendDocuments Started');

        $bulkPolicyDocumentSendCodes = ApplicationStorage::where('key_name', ApplicationStorageEnums::BULK_POLICY_DOCUMENT_SEND_CODES)->first()->value;
        if (empty($bulkPolicyDocumentSendCodes)) {
            LoggerService::error('PolicyBulkSendDocuments - No codes provided in the $codes array.');
            return 0;
        }
        $codes = explode(',', $bulkPolicyDocumentSendCodes);

        if (empty($codes)) {
            LoggerService::error('No codes provided in the $codes array.');

            return 1;
        }

        $count = 0;
        $notFound = [];

        foreach ($codes as $code) {
            if (! $code) {
                LoggerService::info('PolicyBulkSendDocuments - Code is not found.');

                continue;
            }

            $quoteObject = PersonalQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
            if (! $quoteObject) {
                $notFound[] = $code;
                LoggerService::info('PolicyBulkSendDocuments - Code is not found.');

                continue;
            }

            $modelType =  QuoteType::select('code')->find($quoteObject->quote_type_id);
            if(!$modelType){
                $notFound[] = $code;
                LoggerService::info('PolicyBulkSendDocuments - Quote Type is not found.');
                continue;
            }

            $quote = $this->getQuoteObjectBy($modelType->code, $code, 'code');

            $payload = (object) [
                'model_type' => $modelType->code,
                'quote_id' => $quote->id,
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
