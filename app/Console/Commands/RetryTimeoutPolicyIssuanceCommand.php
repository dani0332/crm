<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Jobs\PolicyIssuanceJob;
use App\Repositories\PolicyIssuanceRepository;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Console\Command;

class RetryTimeoutPolicyIssuanceCommand extends Command
{
    private $className = 'retryTimeoutPolicyIssuanceCommand';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy-issuance-automation:retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-attempt timeout policy issuance processes';

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

        $policyIssuanceProcesses = PolicyIssuanceRepository::policyIssuanceByStatus(PolicyIssuanceEnum::TIMEOUT_STATUS);

        if (count($policyIssuanceProcesses) > 0) {
            foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
                $quoteType = $policyIssuanceProcess?->quote_type;
                $insuranceProvider = $policyIssuanceProcess?->insuranceProvider;
                $isPolicyIssuanceAutomationEnabled = (new PolicyIssuanceService)->isPolicyIssuanceAutomationEnabled($quoteType, $insuranceProvider?->code);
                $isPolicyIssuanceAutomationRetryEnabledForTimeout = (new PolicyIssuanceService)->isPolicyIssuanceAutomationRetryEnabledForTimeout($quoteType, $insuranceProvider?->code);

                if ($isPolicyIssuanceAutomationEnabled && $isPolicyIssuanceAutomationRetryEnabledForTimeout) {

                    info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' for Insurance Provider ID: '.$policyIssuanceProcess->insurance_provider_id);
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation1');
                } else {
                    info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - '.$insuranceProvider?->text.' '.$quoteType.'  Policy Issuance Automation and Retry is disabled');
                }
            }
        } else {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - No Policy Issuance Process found to retry');
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }
}
