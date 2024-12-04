<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Jobs\PolicyIssuanceJob;
use App\Repositories\PolicyIssuanceRepository;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
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

        $policyIssuanceProcesses = PolicyIssuanceRepository::policyIssuanceByStatus([PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS]);

        if (count($policyIssuanceProcesses) > 0) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - Total Policy Issuance Processes Count : '.count($policyIssuanceProcesses));
            $insurerAutomationStatus = (new PolicyIssuanceService)->getInsurerAutomationStatus($policyIssuanceProcesses);
            $this->processPolicyIssuanceRecords($policyIssuanceProcesses, $insurerAutomationStatus);
        } else {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - No Policy Issuance Process found');
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }

    private function processPolicyIssuanceRecords($policyIssuanceProcesses, $insurerAutomationStatus)
    {
        foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
            $quoteType = $policyIssuanceProcess?->quote_type;
            $insuranceProvider = $policyIssuanceProcess?->insuranceProvider;
            $isAutomationEnabled = isset($insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType]) && $insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType];
            $isAutomationRetryEnabled = isset($insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType.'_retry']) && $insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType];
            if ($isAutomationEnabled) {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' with status '.$policyIssuanceProcess->status.' for Insurer : '.$insuranceProvider?->text);
                $this->dipatchAutomationJob($policyIssuanceProcess, $isAutomationRetryEnabled);
            } else {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - '.$insuranceProvider?->text.' '.$quoteType.' Automation is disabled');
            }
        }
    }

    private function dipatchAutomationJob($policyIssuanceProcess, $isAutomationRetryEnabled)
    {

        if ($policyIssuanceProcess->status === PolicyIssuanceEnum::PENDING_STATUS) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' dispatch automation job');
            PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation');
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' automation job dispatched');
        } elseif ($policyIssuanceProcess->status === PolicyIssuanceEnum::TIMEOUT_STATUS && $isAutomationRetryEnabled) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' Retry is enabled');
            PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation');
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' retry automation job dispatched');

        } elseif ($policyIssuanceProcess->status === PolicyIssuanceEnum::TIMEOUT_STATUS && ! $isAutomationRetryEnabled) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' Retry is disabled');
        }

    }

}
