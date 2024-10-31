<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Jobs\PolicyIssuanceJob;
use App\Repositories\PolicyIssuanceRepository;
use Illuminate\Console\Command;

class PolicyIssuanceCommand extends Command
{
    private $className = 'policyIssuanceCommand';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy-issuance-automation:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue instant policy for insurance providers';

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

        if (isAllianceTravelAutomationEnabled()) {

            $policyIssuanceProcesses = PolicyIssuanceRepository::policyIssuanceByStatus(PolicyIssuanceEnum::PENDING_STATUS);

            if (count($policyIssuanceProcesses) > 0) {
                foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
                    info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' for Insurance Provider ID: '.$policyIssuanceProcess->insurance_provider_id);
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess)->onQueue('policy-issuance-automation');
                }
            } else {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - No Policy Issuance Process found');
            }

        } else {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - Alliance Travel Automation is disabled');
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }

}
