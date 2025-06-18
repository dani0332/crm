<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
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
        $businessCodes = [
            'BUS-QMF4TU39',
        ];
        $carCodes = [
            'CAR-BRDVRA2K', 'CAR-FS2A38AN', 'CAR-GMAYKRW6',
            'CAR-HVCSRFT8', 'CAR-KSJS4YJ5', 'CAR-RUMR9GWQ',
            'CAR-TK4TEPX4'
        ];
        $healthCodes = [
            'HEA-CTMVEMR6',
            'HEA-H36X57PD'
        ];
        $travelCodes = [
            'TRA-YAC8UESV'
        ];
        $codes = [
            ...$businessCodes,  
            ...$carCodes,
            ...$healthCodes,
            ...$travelCodes
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

            $modelType = null;
            $quoteObject = null;

            if (in_array($code, $businessCodes, true)) {
                $quoteObject = BusinessQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Business;
            } elseif (in_array($code, $carCodes, true)) {
                $quoteObject = CarQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Car;
            } elseif (in_array($code, $healthCodes, true)) {
                $quoteObject = HealthQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Health;
            } elseif (in_array($code, $travelCodes, true)) {
                $quoteObject = TravelQuote::where('code', $code)->where('quote_status_id', QuoteStatusEnum::PolicyBooked)->latest()->first();
                $modelType = quoteTypeCode::Travel;
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
