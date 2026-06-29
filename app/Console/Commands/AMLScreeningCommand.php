<?php

namespace App\Console\Commands;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AmlScreeningAutomationJob;
use App\Models\AmlAutomation;
use App\Services\AML\AMLAutomationService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class AMLScreeningCommand extends Command
{
    use GenericQueriesAllLobs;

    private string $className = 'AMLScreeningCommand';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aml-screening-automation:run {--quote-type= : Specific quote type to process (Travel, Cyber, or Device). If not provided, processes all.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run AML Screening Automation for Travel, Cyber, and Device Quotes';

    public function __construct(
        private readonly AMLAutomationService $eligibilityService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Exits early if the global AML automation feature flag is disabled.
     * For each resolved LOB, runs a broad DB pre-filter then delegates per-quote
     * eligibility to {@see AmlAutomationEligibilityService} before dispatching.
     */
    public function handle(): void
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
     *
     * A broad DB query pre-filters obvious ineligible rows before loading full quote
     * objects. The per-quote eligibility check (via {@see AmlAutomationEligibilityService})
     * is the authoritative gate; it exits early on the first failed condition, avoiding
     * wasted job dispatches for large user sets.
     */
    private function processQuoteType(QuoteTypes $quoteType): void
    {
        $quoteModel = $this->getModelObject(strtolower($quoteType->value));

        if (! class_exists($quoteModel)) {
            LoggerService::info($this->className.' - Model not found for '.$quoteType->value);

            return;
        }

        $date = now()->subWeek()->startOfDay();

        // Broad pre-filter: only load quotes that could possibly pass eligibility.
        // Per-quote precision checks happen below via the eligibility service.
        $quoteRequestQuery = $quoteModel::select('id', 'code', 'api_issuance_status_id', 'aml_status')
            ->where('created_at', '>', $date)
            ->where('api_issuance_status_id', PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID)
            ->where(function ($query) {
                $query->whereNull('aml_status')
                    ->orWhere('aml_status', AMLStatusCode::AMLPending);
            })
            ->whereDoesntHave('amlAutomation');

        // PersonalQuote-backed LOBs (Cyber, Device) share the table;
        // filter by quote_type_id to scope to the correct LOB.
        if (in_array($quoteType, [QuoteTypes::CYBER, QuoteTypes::DEVICE], true)) {
            $quoteRequestQuery->where('quote_type_id', $quoteType->id());
        }

        if ($quoteType === QuoteTypes::DEVICE) { // process Device AML after policy booking only as per FRD.
            $quoteRequestQuery
                ->where('quote_status_id', '=', QuoteStatusEnum::PolicyBooked);
        }

        if (! $quoteRequestQuery->exists()) {
            return;
        }

        $quoteRequestQuery->chunk(100, function ($quoteRequests) use ($quoteType) {
            foreach ($quoteRequests as $quoteRequest) {
                $quoteRequestId = $quoteRequest->id;
                $quote = $this->getQuoteObject($quoteType->value, $quoteRequestId);

                if (! $quote) {
                    LoggerService::error($this->className.' - Quote not found', extra: [
                        'quote_id' => $quoteRequestId,
                        'quote_type' => $quoteType->value,
                    ]);

                    continue;
                }

                $quoteContext = [
                    'quote_id' => $quote->id,
                    'quote_code' => $quote->code,
                    'quote_type' => $quoteType->value,
                ];

                // Load insurance provider once — eligibility checks read it without extra queries.
                $quote->loadMissing('insuranceProvider');

                $eligibility = $this->eligibilityService->check($quoteType, $quote);

                if (! $eligibility->isEligible()) {
                    LoggerService::info($this->className.' - Quote ineligible for AML automation', extra: array_merge($quoteContext, [
                        'reason_code' => $eligibility->reasonCode,
                        'reason' => $eligibility->reason,
                    ]));

                    // Persist the reason so operators can diagnose skipped entries.
                    // Failed status allows re-dispatch via the IMCRM API once the issue is resolved,
                    // while preventing the command from re-queuing the same quote on the next run.
                    $automation = AmlAutomation::updateOrCreate(
                        ['code' => $quote->code],
                        ['status' => AmlAutomationStatus::Failed->value, 'result' => $eligibility->reason]
                    );

                    LoggerService::info($this->className.' - AmlAutomation record updated with ineligibility reason', extra: array_merge($quoteContext, [
                        'aml_automation_id' => $automation->id,
                        'aml_automation_status' => $automation->status,
                    ]));

                    continue;
                }

                $automation = AmlAutomation::updateOrCreate(
                    ['code' => $quote->code],
                    ['status' => AmlAutomationStatus::Queue->value]
                );

                LoggerService::info($this->className.' - Quote queued for AML automation', extra: array_merge($quoteContext, [
                    'aml_automation_id' => $automation->id,
                ]));

                AmlScreeningAutomationJob::dispatch($quoteType, $quote)->onQueue('renewals');
            }
        });
    }

    /**
     * Resolve which LOBs to process.
     *
     * Without `--quote-type`, all supported LOBs are processed.
     * An invalid or unsupported value prints an error and returns empty (halts processing).
     *
     * @return list<QuoteTypes>
     */
    private function resolveQuoteTypes(?string $quoteTypeOption): array
    {
        $supportedQuoteTypes = [QuoteTypes::TRAVEL, QuoteTypes::CYBER, QuoteTypes::DEVICE];

        if (! $quoteTypeOption) {
            return $supportedQuoteTypes;
        }

        $normalizedOption = ucfirst(strtolower($quoteTypeOption)); // Note:: when we add HomeAppliance we need to update this to support multiple word enums like HomeAppliance because without update it will converts to Homeappliance and it will not match with enum value and it will throw error. So we need to update this to support multiple word enums like HomeAppliance => HomeAppliance
        $quoteType = QuoteTypes::tryFrom($normalizedOption);

        if (! $quoteType || ! in_array($quoteType, $supportedQuoteTypes, true)) {
            $this->error("Invalid quote type. Must be 'Travel', 'Cyber', or 'Device'.");

            return [];
        }

        return [$quoteType];
    }
}
