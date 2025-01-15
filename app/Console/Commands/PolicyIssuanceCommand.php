<?php

namespace App\Console\Commands;

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
        (new PolicyIssuanceService)->executePolicyIssuanceAutomationSteps();
        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }

}
