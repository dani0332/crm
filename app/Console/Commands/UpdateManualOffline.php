<?php

namespace App\Console\Commands;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateManualOffline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ResetManualOfflineUpdate:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset reset manual offline';

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
        info('Scheduler is about to reset manual offline');

        User::query()->where('status', UserStatusEnum::MANUAL_OFFLINE)->update([
            'status' => UserStatusEnum::OFFLINE,
        ]);

        info('Scheduler has reset manual offline to offline for all users');

        return 0;
    }

}
