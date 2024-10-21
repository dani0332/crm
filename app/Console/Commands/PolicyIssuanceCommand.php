<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\SageEnum;
use App\Factories\PolicyIssuanceFactory;
use App\Jobs\BookPolicyOnSageJob;
use App\Jobs\PolicyIssuanceJob;
use App\Jobs\SendUpdateSageJob;
use App\Models\PolicyIssuance;
use App\Models\SageProcess;
use Illuminate\Console\Command;

class PolicyIssuanceCommand extends Command
{
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
        info('cmd:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Started');

        $policyIssuanceProcesses = PolicyIssuance::where('status', PolicyIssuanceEnum::PENDING_STATUS)
            ->orderBy('created_at')
            ->get();

        if (count($policyIssuanceProcesses) > 0) {
            foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {

                info('cmd:'.basename(__CLASS__).' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' for Insurance Provider ID: '.$policyIssuanceProcess->insurance_provider_id);
                PolicyIssuanceJob::dispatch($policyIssuanceProcess)->onQueue('policy-issuance-automation');
            }
        } else {
            info('cmd:'.basename(__CLASS__).' fn:'.__FUNCTION__.' - No Policy Issuance Process found');
        }

        info('cmd:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Ended');
    }
}
