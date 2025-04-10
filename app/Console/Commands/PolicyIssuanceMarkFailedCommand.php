<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteTypes;
use App\Jobs\SendTravelAllianceFailedAllocationEmailJob;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;
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
        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Started');

        (new PolicyIssuanceService)->processStuckPolicyIssuances();

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Ended');
    }
}
