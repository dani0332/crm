<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Jobs\myalfredMigrationJob;
use Illuminate\Console\Command;


use Illuminate\Support\Arr;


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
        $customers = Customer::doesnthave('MyAlfredUsers')->get();
        $customerChunk = $customers->chunk(100);
        $bar = $this->output->createProgressBar(count($customers));
        $bar->start();
        foreach ($customerChunk as $customer) {
            myalfredMigrationJob::dispatch($customer);
            sleep(0.5);
        }

        $bar->advance();

        $bar->finish();
    }
}
