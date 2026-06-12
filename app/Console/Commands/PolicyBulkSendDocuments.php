<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\SageEnum;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\SageProcess;
use App\Models\SendUpdateLog;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PolicyBulkSendDocuments extends Command
{
    use GenericQueriesAllLobs;

    protected $signature = 'policy:bulk-send-documents {--from-sage-process : Fetch failed sage processes instead of codes from storage} {--start-date= : Filter sage processes from this date (Y-m-d format)} {--sage-process-id= : Filter by specific sage process ID}';
    protected $description = 'Bulk send policy documents for multiple quote codes';

    public function handle(): int
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SEND_AND_BOOK_POLICY_FAILED_BULK_EMAIL_JOB);
        LoggerService::info('PolicyBulkSendDocuments: Command started');

        $fromSageProcess = $this->option('from-sage-process');
        $startDate = $this->option('start-date');
        $sageProcessId = $this->option('sage-process-id');

        if ($fromSageProcess) {
            return $this->handleFromSageProcesses($startDate, $sageProcessId);
        }

        $codes = $this->getCodesFromStorage();

        if ($codes->isEmpty()) {
            LoggerService::info('PolicyBulkSendDocuments: No codes found in storage configuration');

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
                LoggerService::info("PolicyBulkSendDocuments: Failed to process code: {$code}");
            }
        }

        LoggerService::info("PolicyBulkSendDocuments: Successfully processed {$successCount} quotes");

        if (! empty($notFound)) {
            LoggerService::info('PolicyBulkSendDocuments: '.count($notFound).' codes not found: '.implode(', ', $notFound));
        }

        LoggerService::info('PolicyBulkSendDocuments: Command completed', [
            'success_count' => $successCount,
            'not_found_count' => count($notFound),
        ]);

        return Command::SUCCESS;
    }

    private function getCodesFromStorage(): Collection
    {
        $storage = ApplicationStorage::where('key_name', ApplicationStorageEnums::BULK_POLICY_DOCUMENT_SEND_CODES)->first();

        if (! $storage || empty($storage->value)) {
            return collect();
        }

        return collect(explode(',', $storage->value))
            ->map(fn ($code) => trim($code))
            ->filter(fn ($code) => ! empty($code));
    }

    private function handleFromSageProcesses(?string $startDate = null, ?string $sageProcessId = null): int
    {
        LoggerService::info('Fetching failed sage processes', extra: [
            'start_date' => $startDate,
            'sage_process_id' => $sageProcessId,
        ]);

        // Get failed sage processes similar to scheduleSageProcesses
        $query = SageProcess::where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)->where('model_type', '!=', SendUpdateLog::class);

        // Apply sage process ID filter if provided
        if ($sageProcessId) {
            $query->where('id', $sageProcessId);
            LoggerService::info("Filtering by sage process ID: {$sageProcessId}");
        }

        // Apply start date filter if provided
        if ($startDate) {
            try {
                $parsedDate = Carbon::parse($startDate)->startOfDay();
                $query->where('created_at', '>=', $parsedDate);
                LoggerService::info("Filtering from date: {$parsedDate}");
            } catch (\Exception $e) {
                LoggerService::info("Invalid date format provided: {$startDate}");

                return Command::FAILURE;
            }
        }

        $sageProcesses = $query->orderBy('created_at')->get();

        if ($sageProcesses->isEmpty()) {
            LoggerService::info('No failed sage processes found');

            return Command::FAILURE;
        }

        LoggerService::info("Processing {$sageProcesses->count()} failed sage processes...");

        $successCount = 0;
        $failedProcesses = [];

        foreach ($sageProcesses as $sageProcess) {
            try {
                // Decode the request JSON to get model_type and quote_id
                $requestData = json_decode($sageProcess->request, true);

                if (! $requestData || ! isset($requestData['requestPayload']['model_type']) || ! isset($requestData['requestPayload']['quote_id'])) {
                    LoggerService::info('Invalid request data for sage process', extra: [
                        'sage_process_id' => $sageProcess->id,
                    ]);

                    if ($requestData['sagePayload']['sageProcessRequestType'] == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                        $failedProcesses[] = $sageProcess->id;
                    }

                    continue;
                }

                $modelType = $requestData['requestPayload']['model_type'];
                $quoteId = $requestData['requestPayload']['quote_id'];

                LoggerService::info('Processing sage process', extra: [
                    'sage_process_id' => $sageProcess->id,
                    'model_type' => $modelType,
                    'quote_id' => $quoteId,
                ]);

                // Get the quote using the model_type and quote_id
                $quote = $this->getQuoteObjectBy($modelType, $quoteId, 'id');

                if (! $quote) {
                    LoggerService::info('Quote not found for sage process', extra: [
                        'sage_process_id' => $sageProcess->id,
                    ]);
                    $failedProcesses[] = $sageProcess->id;

                    continue;
                }

                $payload = (object) [
                    'model_type' => $modelType,
                    'quote_id' => $quoteId,
                ];

                // Set the correct modelType for business quotes based on business_type_of_insurance_id to ensure the right template is used.
                if ($modelType == quoteTypeCode::Business) {
                    if ($quote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                        $payload->modelType = quoteTypeCode::GroupMedical;
                    } else {
                        $payload->modelType = quoteTypeCode::CORPLINE;
                    }
                }

                LoggerService::info('Payload Before Dispatch', extra: ['Payload' => json_encode($payload)]);
                SendBookPolicyDocumentsJob::dispatch($payload, $quote->code, true, true);
                $successCount++;
                LoggerService::info('Job dispatched for sage process', extra: [
                    'sage_process_id' => $sageProcess->id,
                    'quote_code' => $quote->code,
                ]);
            } catch (\Exception $e) {
                LoggerService::info('Exception processing sage process', extra: [
                    'sage_process_id' => $sageProcess->id,
                    'error' => $e->getMessage(),
                ]);
                $failedProcesses[] = $sageProcess->id;
            }
        }

        LoggerService::info("Successfully processed {$successCount} sage processes");

        if (! empty($failedProcesses)) {
            LoggerService::info('PolicyBulkSendDocuments: '.count($failedProcesses).' sage processes failed: '.implode(', ', $failedProcesses));
        }

        LoggerService::info('Command completed', extra: [
            'success_count' => $successCount,
            'failed_count' => count($failedProcesses),
        ]);

        return Command::SUCCESS;
    }

    private function processCode(string $code): bool
    {
        $quoteObject = PersonalQuote::where('code', $code)
            ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->latest()
            ->first();

        if (! $quoteObject) {
            LoggerService::info("PolicyBulkSendDocuments: Quote not found for code in personal quote table: {$code}");

            return false;
        }

        $quoteType = QuoteType::select('code')->find($quoteObject->quote_type_id);
        if (! $quoteType) {
            LoggerService::info("PolicyBulkSendDocuments: Quote type not found for code: {$code}");

            return false;
        }

        $quote = $this->getQuoteObjectBy($quoteType->code, $code, 'code');
        if (! $quote) {
            LoggerService::info("PolicyBulkSendDocuments: Quote not found for code in quote table: {$code}");

            return false;
        }

        $payload = (object) [
            'model_type' => $quoteType->code,
            'quote_id' => $quote->id,
        ];

        // Set the correct modelType for business quotes based on business_type_of_insurance_id to ensure the right template is used.
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
