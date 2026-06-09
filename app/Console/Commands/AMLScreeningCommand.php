<?php

namespace App\Console\Commands;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AmlScreeningAutomationJob;
use App\Models\AmlAutomation;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class AMLScreeningCommand extends Command
{
    use GenericQueriesAllLobs;

    private $className = 'AMLScreeningCommand';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aml-screening-automation:run {--quote-type= : Specific quote type to process (Travel or Cyber). If not provided, processes both.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run AML Screening Automation for Travel and Cyber Quotes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isAmlAutomationEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED);
        if (! $isAmlAutomationEnabled) {
            LoggerService::info($this->className.' is not enabled from cms');

            return;
        }

        $quoteTypesToProcess = $this->resolveQuoteTypes($this->option('quote-type'));
        if (empty($quoteTypesToProcess)) {
            return;
        }

        foreach ($quoteTypesToProcess as $quoteType) {
            $this->processQuoteType($quoteType);
        }
    }

    /**
     * Process AML screening automation for a specific quote type.
     */
    private function processQuoteType(QuoteTypes $quoteType): void
    {
        $quoteModel = $this->getModelObject(strtolower($quoteType->value));

        if (! class_exists($quoteModel)) {
            LoggerService::info($this->className.' - Model not found for '.$quoteType->value);

            return;
        }

        $date = now()->subWeek()->startOfDay();
        $quoteRequestQuery = $quoteModel::select('id', 'code', 'api_issuance_status_id', 'aml_status')
            ->where('created_at', '>', $date)
            ->where('api_issuance_status_id', PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID)
            ->where(function ($query) {
                $query->whereNull('aml_status')
                    ->orWhere('aml_status', AMLStatusCode::AMLPending);
            })
            ->whereDoesntHave('amlAutomation');

        // For PersonalQuote (Cyber), also filter by quote_type_id
        if ($quoteType === QuoteTypes::CYBER) {
            $quoteRequestQuery->where('quote_type_id', $quoteType->id());
        }

        if ($quoteRequestQuery->exists()) {
            $quoteRequestQuery->chunk(100, function ($quoteRequests) use ($quoteType) {
                foreach ($quoteRequests as $quoteRequest) {

                    $quoteRequestId = $quoteRequest->id;
                    $quoteRequest = $this->getQuoteObject($quoteType->value, $quoteRequestId);

                    if (! $quoteRequest) {
                        LoggerService::error($this->className.' - '.$quoteType->value.' Quote #'.$quoteRequestId.' not found');

                        continue;
                    }

                    $isApiIssuanceStatusYes = $quoteRequest->api_issuance_status_id == PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
                    $isAMLPending = empty($quoteRequest->aml_status) ?: $quoteRequest->aml_status == AMLStatusCode::AMLPending;

                    // Check amlAutomation relationship based on quote type
                    $hasAmlAutomation = false;
                    if ($quoteType === QuoteTypes::TRAVEL) {
                        $hasAmlAutomation = $quoteRequest->amlAutomation()->exists();
                    } elseif ($quoteType === QuoteTypes::CYBER) {
                        $hasAmlAutomation = AmlAutomation::where('code', $quoteRequest->code)->exists();
                    }

                    if (! $isApiIssuanceStatusYes || ! $isAMLPending || $hasAmlAutomation) {
                        continue;
                    }

                    AmlAutomation::updateOrCreate(['code' => $quoteRequest->code], ['status' => AmlAutomationStatus::Queue->value]);
                    AmlScreeningAutomationJob::dispatch($quoteType, $quoteRequest)->onQueue('renewals');
                }
            });
        }
    }

    private function resolveQuoteTypes(?string $quoteTypeOption): array
    {
        if (! $quoteTypeOption) {
            return [QuoteTypes::TRAVEL, QuoteTypes::CYBER];
        }

        $normalizedQuoteTypeOption = ucfirst(strtolower($quoteTypeOption));
        $quoteType = QuoteTypes::tryFrom($normalizedQuoteTypeOption);

        if (! $quoteType || ! in_array($quoteType, [QuoteTypes::TRAVEL, QuoteTypes::CYBER], true)) {
            $this->error("Invalid quote type. Must be 'Travel' or 'Cyber'.");

            return [];
        }

        return [$quoteType];
    }
}
