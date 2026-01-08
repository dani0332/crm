<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MisReportJob;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;

class MisReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mis-report:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch MisReportJob to generate and send MIS report';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        LoggerService::info('cmd:MisReportCommand - Starting MisReportJob dispatch');

        try {
            MisReportJob::dispatch();

            LoggerService::info('cmd:MisReportCommand - MisReportJob dispatched successfully');

            $this->info('MisReportJob has been dispatched successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            LoggerService::error('cmd:MisReportCommand - Failed to dispatch MisReportJob', exception: $e);

            $this->error('Failed to dispatch MisReportJob: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
