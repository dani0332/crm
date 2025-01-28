<?php

namespace App\Console\Commands;

use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PolicyIssuanceDataCleanUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy-issuance-automation:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete completed policy issuance processes which are created more than 30 days ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = Carbon::now()->subDays(30);

        PolicyIssuance::where('status', PolicyIssuanceEnum::COMPLETED_STATUS)
            ->where('created_at', '<', $date)
            ->delete();

        info('Policy Issuance Automation Data Clean Up Command executed successfully.', [' data before date' => $date]);
    }
}
