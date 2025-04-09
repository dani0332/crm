<?php

namespace App\Console\Commands;

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

        $quoteRequestQuery = $quoteModel::select('id', 'api_issuance_status_id', 'aml_status')
            ->where([
                'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
                'aml_status' => AMLStatusCode::AMLPending,
            ]);

        $quoteRequestCount = $quoteRequestQuery->count();

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' '.$quoteRequestCount.' Record Found');
        if ($quoteRequestCount > 0) {
            $quoteRequestQuery->chunk(100, function ($quoteRequests) {
                foreach ($quoteRequests as $quoteRequest) {
                    AmlScreeningAutomationJob::dispatch($quoteRequest->id, $this->quoteType)->onQueue('aml-screening-automation');
                }
            });
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }
}
