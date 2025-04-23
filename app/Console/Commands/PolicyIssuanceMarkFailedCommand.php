<?php

namespace App\Console\Commands;

use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Console\Command;

class PolicyIssuanceMarkFailedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy-issuance:mark-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identify policy issuance entries stuck in progress for more than 15 minutes and mark as failed';

    private string $className = 'policyIssuanceMarkFailedCommand';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        LoggerService::info('cmd:'.$this->className.' fn:handle Started');

        (new PolicyIssuanceService)->processStuckPolicyIssuances();

        LoggerService::info('cmd:'.$this->className.' fn:handle Ended');
    }
}
