<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\MyAlFredUser;
use Carbon\Carbon;


class myalfredMigrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $flatCustomerArray;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($flatCustomerArray)
    {
        $this->flatCustomerArray = $flatCustomerArray;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // basic auth
        $magicUrlGenerateUserName = Config::get('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $magicUrlGeneratePassword = Config::get('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $magicUrlGeneratauthBasic = base64_encode($magicUrlGenerateUserName . ":" . $magicUrlGeneratePassword);

        // loop on chunk
        foreach ($this->flatCustomerArray as $item) {

            //check if user exists
            $customer = MyAlFredUser::where('customer_id', $item->id)->first();

            if (!$customer) {
                // API call
                $response = Http::withHeaders([
                    'Authorization' =>  'Basic ' . $magicUrlGeneratauthBasic
                ])->post(env('BERLIN_API_ENDPOINT') . '/auth/generate-url');
                $responseBody = json_decode($response->body());

                // create record
                MyAlFredUser::insert([
                    'signup_url' => $responseBody->data->url,
                    'customer_id' => $item->id,
                    'code' => substr($responseBody->data->url, strpos($responseBody->data->url, "signup/") + 7),
                    'source' => 'MIGRATION',
                    'created_at' => Carbon::now('Asia/Dubai'),
                    'updated_at' => Carbon::now('Asia/Dubai')
                ]);
            }
        }
    }
}
