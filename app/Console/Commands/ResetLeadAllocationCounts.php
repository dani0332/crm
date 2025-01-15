<?php

namespace App\Console\Commands;

use App\Enums\UserStatusEnum;
use App\Models\LeadAllocation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetLeadAllocationCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ResetLeadAllocationCounts:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset users allocation count every night';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('Scheduler is about to reset lead allocation counts for all users');
        $this->resetLeadAllocationCounts();
        info('Scheduler has reset lead allocation counts for all users');

        info('Scheduler is about to reset max capacity for every user');
        $this->resetNormalLeadAllocationCapacity();
        info('Scheduler has reset max capacity for all users where reset_cap was true');

        info('Scheduler is about to reset buy leads max capacity for every user');
        $this->resetBuyLeadAllocationCapacity('buy_lead_reset_capacity');
        info('Scheduler has reset buy leads max capacity for all users where buy_lead_reset_capacity was true');

        DB::table('sessions')->delete(); // truncate sessions table

        $this->resetUserStatuses();

        return 0;
    }

    private function resetLeadAllocationCounts()
    {
        LeadAllocation::query()->update([
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
            'buy_lead_allocation_count' => 0,
        ]);
    }

    private function resetUserStatuses()
    {
        User::query()->where('is_active', 1)
            ->whereNotIn('status', [UserStatusEnum::LEAVE, UserStatusEnum::SICK])
            ->update([
                'status' => UserStatusEnum::UNAVAILABLE,
            ]);
    }

    private function resetNormalLeadAllocationCapacity()
    {
        LeadAllocation::query()->where('reset_cap', 1)->update([
            'max_capacity' => 20,
        ]);
    }

    private function resetBuyLeadAllocationCapacity(string $resetColumn)
    {
        LeadAllocation::query()
            ->where($resetColumn, 1)
            ->whereIn('quote_type_id', [1, 3])
            ->update([
                'buy_lead_status' => true,
                'buy_lead_max_capacity' => DB::raw('CASE WHEN quote_type_id = 1 THEN 5 ELSE 50 END'),
            ]);
    }
}
