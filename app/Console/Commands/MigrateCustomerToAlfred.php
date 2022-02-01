<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\MyAlFredUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
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
        $customers = Customer::doesnthave('MyAlfredUsers')->where('has_alfred_access', 1)->where('has_reward_access', 1)->where('is_we_sent', 0)->get();
        $customerChunk = $customers->chunk(5);
        $bar = $this->output->createProgressBar(count($customers));

        $bar->start();
        $magicUrlGenerateUserName = Config::get('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $magicUrlGeneratePassword = Config::get('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $magicUrlGeneratauthBasic = base64_encode($magicUrlGenerateUserName . ":" . $magicUrlGeneratePassword);
        $flatted = Arr::flatten($customerChunk, 1);
        $alfredInsert = [];

        foreach ($flatted as $item) {

            $response = Http::withHeaders([
                'Authorization' =>  'Basic ' . $magicUrlGeneratauthBasic
            ])->post(env('BERLIN_API_ENDPOINT') . '/auth/generate-url');
            $responseBody = json_decode($response->body());
            $customer = MyAlFredUser::where('customer_id', $item->id)->first();
            if (!$customer) {

                $alfredInsert[] = [
                    'signup_url' => $responseBody->data->url,
                    'customer_id' => $item->id,
                    'created_at' => Carbon::now('Asia/Dubai'),
                    'updated_at' => Carbon::now('Asia/Dubai')
                ];
            }
            sleep(1);

            $bar->advance();
        }

        MyAlFredUser::insert($alfredInsert);

        $bar->finish();
    }
}
