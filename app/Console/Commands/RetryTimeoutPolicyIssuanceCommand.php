<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Jobs\PolicyIssuanceJob;
use App\Repositories\PolicyIssuanceRepository;
use Illuminate\Console\Command;

class RetryTimeoutPolicyIssuanceCommand extends Command
{
    private $className = null;
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

        $this->className = basename(__CLASS__);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Started');

        if (isAllianceTravelAutomationEnabled() && isAllianceTravelPolicyIssuanceRetryEnabledForTimeout()) {

            $policyIssuanceProcesses = PolicyIssuanceRepository::policyIssuanceByStatus(PolicyIssuanceEnum::TIMEOUT_STATUS);

            if (count($policyIssuanceProcesses) > 0) {
                foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {

                    info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' for Insurance Provider ID: '.$policyIssuanceProcess->insurance_provider_id);
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess)->onQueue('policy-issuance-automation');
                }
            } else {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - No Policy Issuance Process found to retry');
            }
        } else {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - Alliance Travel Policy Issuance Automation and Retry is disabled');
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }
}
