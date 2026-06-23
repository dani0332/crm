<?php

namespace App\Jobs;

use App\Models\PqaLeadAllocationConfig;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResetPqaAllocationCountJob implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function handle(): void
    {
        LoggerService::info(self::class.'::handle - Resetting PQA allocation counts');

        PqaLeadAllocationConfig::query()
            ->where('reset_cap', 0)
            ->update([
                'manual_assignment_count' => 0,
                'auto_assignment_count' => 0,
                'allocation_count' => 0,
            ]);

        PqaLeadAllocationConfig::query()
            ->where('reset_cap', 1)
            ->update([
                'manual_assignment_count' => 0,
                'auto_assignment_count' => 0,
                'allocation_count' => 0,
                'max_capacity' => 100,
            ]);

        LoggerService::info(self::class.'::handle - PQA allocation counts reset completed');
    }
}
