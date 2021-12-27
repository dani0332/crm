<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\MyAlFredUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


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
        $customers = Customer::get();
        $customerChunk = $customers->chunk(2);

        $bar = $this->output->createProgressBar(count($customers));
        
        $bar->start();

        $customerChunk->each(function ($chunk) use ($bar) {
            $chunk->each(function ($item) use ($bar) {
                $response = Http::post(env('BERLIN_API_ENDPOINT'));
                $responseBody = json_decode($response->body());
                MyAlFredUser::insert([
                    'signup_url' => $responseBody->data->dataValues->url,
                    'customer_id' => $item->id
                ]);
                $bar->advance();
            });
        });

        $bar->finish();
    }
}
