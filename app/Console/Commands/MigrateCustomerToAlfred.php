<?php

namespace App\Console\Commands;

use App\Jobs\myalfredMigrationJob;
use App\Models\Customer;
use Illuminate\Console\Command;

class migrateCustomerToAlfred extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:customerToAlfred';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to move Customers to alfred with unique url';

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
        $count = Customer::where('has_alfred_access', 1)->where('has_reward_access', 1)->where('is_we_sent', 0)->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        Customer::where('has_alfred_access', 1)->where('has_reward_access', 1)->where('is_we_sent', 0)->chunk(100, function ($customerChunk) use ($bar) {
            myalfredMigrationJob::dispatch($customerChunk);
            sleep(1);
            $bar->advance();
        });

        $bar->finish();
    }
}
