<?php

namespace App\Console\Commands;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AmlScreeningAutomationJob;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class AMLScreeningCommand extends Command
{
    use GenericQueriesAllLobs;

    private $className = 'AMLScreeningCommand';
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
        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Started');

        $quoteModel = $this->getModelObject(strtolower($this->quoteType->value));

        if (! $quoteModel || ! class_exists($quoteModel)) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote Model not found');

            return;
        }

        $date = now()->yesterday();
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

                    $quoteRequest = $this->getQuoteObject($this->quoteType->value, $quoteRequest->id);

                    if (! $quoteRequest) {
                        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote not found');
                        return;
                    }

                    if ($quoteRequest->api_issuance_status_id != PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID) {
                        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote is not eligible for AML-Automation, due to API issuance status is not yes');
                        return;
                    }

                    if ($quoteRequest->aml_status != AMLStatusCode::AMLPending) {
                        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote is not eligible for AML-Automation, due to AML status is not pending');
                        return;
                    }

                    $amlAutomation = $quoteRequest->amlAutomation();
                    if(!$amlAutomation->exists()){

                        $amlAutomation->create(['code' => $quoteRequest->code, 'status' => AmlAutomationStatus::QUEUE_STATUS]);
                        AmlScreeningAutomationJob::dispatch($this->quoteType, $quoteRequest)->onQueue('renewals');
                    }
                }
            });
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }
}
