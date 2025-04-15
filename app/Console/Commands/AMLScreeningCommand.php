<?php

namespace App\Console\Commands;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AmlScreeningAutomationJob;
use App\Models\AmlAutomation;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AMLScreeningCommand extends Command
{
    use GenericQueriesAllLobs;

    private $className = 'AML Screening Command';
    private $quoteType = QuoteTypes::TRAVEL;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aml-screening-automation:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run AML Screening Automation for Travel Quote';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        LoggerService::info($this->className.' - Started');
        $quoteModel = $this->getModelObject(strtolower($this->quoteType->value));

        if (! class_exists($quoteModel)) {
            LoggerService::critical($this->className.' - Quote Model not found');
            LoggerService::info($this->className.' - Ended');
            return;
        }

        $date = now()->yesterday();
        $quoteRequestQuery = $quoteModel::select('id', 'code', 'api_issuance_status_id', 'aml_status')
            ->where([
                'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
                'aml_status' => AMLStatusCode::AMLPending,
            ])
            ->where('created_at', '>', $date)
            ->whereNotIn(
                'code',
                $quoteModel::from('aml_automation')
                    ->whereNot('status', AmlAutomationStatus::FAILED_STATUS)
                    ->select('code')
            );

        if ($quoteRequestQuery->exists()) {
            $quoteRequestQuery->chunk(100, function ($quoteRequests) {
                foreach ($quoteRequests as $quoteRequest) {

                    $quoteRequest = $this->getQuoteObject($this->quoteType->value, $quoteRequest->id);

                    if (! $quoteRequest) {
                        LoggerService::error($this->className.' - Quote not found');
                        continue;
                    }

                    Log::withContext(['ref_id' => $quoteRequest->code]);
                    if ($quoteRequest->api_issuance_status_id != PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID) {
                        LoggerService::notice($this->className.' - Quote is not eligible for AML, due to API issuance status is not yes');
                        continue;
                    }

                    if ($quoteRequest->aml_status != AMLStatusCode::AMLPending) {
                        LoggerService::notice($this->className.' - Quote is not eligible for AML, due to AML status is not pending');
                        continue;
                    }

                    $amlAutomation = $quoteRequest->amlAutomation;
                    if (isset($amlAutomation->status) && $amlAutomation->status != AmlAutomationStatus::FAILED_STATUS) {
                        LoggerService::warning($this->className.' - Quote is already '.($quoteRequest->amlAutomation->status ?? 'picked'));
                        continue;
                    }

                    AmlAutomation::updateOrCreate(['code' => $quoteRequest->code], ['status' => AmlAutomationStatus::QUEUE_STATUS]);
                    AmlScreeningAutomationJob::dispatch($this->quoteType, $quoteRequest)->onQueue('renewals');
                }
            });
        }

        LoggerService::info($this->className.' - Ended');
    }
}
