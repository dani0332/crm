<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceEnum;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\BusinessTypeOfInsurance;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class PolicyBulkSendDocuments extends Command
{
    use GenericQueriesAllLobs;

    protected $signature = 'policy:bulk-send-documents';
    protected $description = 'Bulk send policy documents for multiple quote codes';

    public function handle(): int
    {
        LoggerService::info('PolicyBulkSendDocuments: Command started');

        $codes = $this->getCodesFromStorage();
        
        if ($codes->isEmpty()) {
            LoggerService::error('PolicyBulkSendDocuments: No codes found in storage configuration');
            return Command::FAILURE;
        }

        LoggerService::info("PolicyBulkSendDocuments: Processing {$codes->count()} quote codes...");
        
        $successCount = 0;
        $notFound = [];

        foreach ($codes as $code) {
            LoggerService::info("PolicyBulkSendDocuments: Processing code: {$code}");
            
            if ($this->processCode($code)) {
                $successCount++;
                LoggerService::info("PolicyBulkSendDocuments: Dispatched job for code: {$code}");
            } else {
                $notFound[] = $code;
                LoggerService::error("PolicyBulkSendDocuments: Failed to process code: {$code}");
            }
        }

        LoggerService::info("PolicyBulkSendDocuments: Successfully processed {$successCount} quotes");
        
        if (!empty($notFound)) {
            LoggerService::warning("PolicyBulkSendDocuments: " . count($notFound) . " codes not found: " . implode(', ', $notFound));
        }

        LoggerService::info("PolicyBulkSendDocuments: Command completed", [
            'success_count' => $successCount,
            'not_found_count' => count($notFound),
        ]);

        return Command::SUCCESS;
    }

    private function getCodesFromStorage(): \Illuminate\Support\Collection
    {
        $storage = ApplicationStorage::where('key_name', ApplicationStorageEnums::BULK_POLICY_DOCUMENT_SEND_CODES)->first();
        
        if (!$storage || empty($storage->value)) {
            return collect();
        }

        return collect(explode(',', $storage->value))
            ->map(fn($code) => trim($code))
            ->filter(fn($code) => !empty($code));
    }

    private function processCode(string $code): bool
    {
        $quoteObject = PersonalQuote::where('code', $code)
            ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->latest()
            ->first();

        if (!$quoteObject) {
            LoggerService::error("PolicyBulkSendDocuments: Quote not found for code in personal quote table: {$code}");
            return false;
        }

        $quoteType = QuoteType::select('code')->find($quoteObject->quote_type_id);
        if (!$quoteType) {
            LoggerService::error("PolicyBulkSendDocuments: Quote type not found for code: {$code}");
            return false;
        }

        $quote = $this->getQuoteObjectBy($quoteType->code, $code, 'code');
        if (!$quote) {
            LoggerService::error("PolicyBulkSendDocuments: Quote not found for code in quote table: {$code}");
            return false;
        }

        $payload = (object) [
            'model_type' => $quoteType->code,
            'quote_id' => $quote->id,
        ];

        // To handle the model type for business quote
        $modelType = $quoteType->code;
        if ($modelType == quoteTypeCode::Business) {
            if ($quote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                $payload->modelType = quoteTypeCode::GroupMedical;
            } else {
                $payload->modelType = quoteTypeCode::CORPLINE;
            }
        }
        
        SendBookPolicyDocumentsJob::dispatch($payload, $code, true);
        LoggerService::info("PolicyBulkSendDocuments: Job dispatched for code: {$code}");
        
        return true;
    }
}
