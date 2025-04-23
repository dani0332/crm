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
        $isAmlAutomationEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AML_AUTOMATION_ENABLED);
        if (! $isAmlAutomationEnabled) {
            LoggerService::info('Aml Automation is not enabled from cms');

            return false;
        }

        $quoteModel = $this->getModelObject(strtolower($this->quoteType->value));

        if (! class_exists($quoteModel)) {
            return;
        }

        $date = now()->subWeek()->startOfDay();
        $quoteRequestQuery = $quoteModel::select('id', 'code', 'api_issuance_status_id', 'aml_status')
            ->where([
                'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
                'aml_status' => AMLStatusCode::AMLPending,
            ])
            ->where('created_at', '>', $date)
            ->whereNotIn('code', $quoteModel::from('aml_automation')->select('code'));

        if ($quoteRequestQuery->exists()) {
            $quoteRequestQuery->chunk(100, function ($quoteRequests) {
                foreach ($quoteRequests as $quoteRequest) {

                    $quoteRequestId = $quoteRequest->id;
                    $quoteRequest = $this->getQuoteObject($this->quoteType->value, $quoteRequestId);

                    if (! $quoteRequest) {
                        LoggerService::error($this->className.' - '.$this->quoteType->value.' Quote #'.$quoteRequestId.' not found');

                        continue;
                    }

                    $isApiIssuanceStatusYes = $quoteRequest->api_issuance_status_id == PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
                    $isAMLPending = $quoteRequest->aml_status == AMLStatusCode::AMLPending;

                    if (! $isApiIssuanceStatusYes || ! $isAMLPending || $quoteRequest->amlAutomation()->exists()) {
                        continue;
                    }

                    AmlAutomation::updateOrCreate(['code' => $quoteRequest->code], ['status' => AmlAutomationStatus::QUEUE_STATUS]);
                    AmlScreeningAutomationJob::dispatch($this->quoteType, $quoteRequest)->onQueue('renewals');
                }
            });
        }
    }
}
