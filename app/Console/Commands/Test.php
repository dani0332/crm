<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::MOTOR_REVIVAL_WORKFLOW)->first();
        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, []);
        if ($response && $response->status_code === 200) {
            LoggerService::info('Motor Revival OCB - Successfully triggered event');
        } else {
            LoggerService::info("Motor Revival OCB - Error triggering event having response status code: {$response?->status_code}");
        }
    }
}
