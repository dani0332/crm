<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\CarQuote;
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
        LoggerService::info('PolicyBulkSendDocuments Started');

        $codes = [
            'HEA-3PJLNKL6',
            'CAR-QK7EHTE5',
            'CAR-UTJB3A2V',
            'CAR-SUQGE9NJ',
            'CAR-6XT6SVA9',
            'CAR-XQBJCYZX',
            'CAR-92HB2DPQ',
            'CAR-QPZRVRTU',
        ];

        if (empty($codes)) {
            $this->error('No codes provided in the $codes array.');

            return 1;
        }

        $count = 0;
        $notFound = [];

        foreach ($codes as $code) {
            if (! $code) {
                LoggerService::info('PolicyBulkSendDocuments - Code is not found.');

                continue;
            }

            if ($code == 'HEA-3PJLNKL6') {
                $quoteObject = HealthQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Health;
            } else {
                $quoteObject = CarQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Car;
            }

            if (! $quoteObject) {
                $notFound[] = $code;
                LoggerService::info('PolicyBulkSendDocuments - Code is not found.');

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
