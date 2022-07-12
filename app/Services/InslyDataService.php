<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\InslyDataMapping;
use App\Models\InslyBatchLog;
use Illuminate\Support\Facades\Http;
use Config;
use GuzzleHttp\Client;


class InslyDataService extends BaseService
{
    public static function GetDataFromInsly($nextStartDate, $nextEndDate)
    {
        $client = new \GuzzleHttp\Client();
        $user = Config::get('constants.INSLY_API_RENEWAL_USERNAME');
        $pass = Config::get('constants.INSLY_API_RENEWAL_PASSWORD');
        $uri = Config::get('constants.INSLY_API_RENEWAL_URI');
        $timeout = Config::get('constants.INSLY_REQUEST_TIMEOUT_IN_SECONDS');


        Log::info('User : ' . $user . ', Pass : ' . $pass . ', URI : ' . $uri);

        $requestBody = array(
            'username' => $user,
            'password' => $pass,
            'policy_date_begin' => $nextStartDate,
            'policy_date_end' => $nextEndDate,
        );

        $inslyRequest = $client->post(
            $uri,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body' => json_encode($requestBody),
                'timeout' => $timeout, // Response timeout
            ]
        );
        return $inslyRequest->getBody();
    }

    public static function GetLastInslyBatchLog()
    {
        return InslyBatchLog::orderBy('created_at', 'desc')->get()->first();
    }

    public static function AddInslyBatchLog($start, $end, $count)
    {
        $batchLog = new InslyBatchLog;
        $batchLog->batch_start_date = $start;
        $batchLog->batch_end_date = $end;
        $batchLog->batch_records_processed = $count;
        $batchLog->save();
    }

    public static function AddInslyRecordInDatabase($customer_name, $customer_email, $policy, $is_corrupt_data)
    {
        $dataMapping = new InslyDataMapping();
        $dataMapping->customer_name = $customer_name;
        $dataMapping->customer_email = $customer_email;
        $dataMapping->insly_data = json_encode($policy);
        $dataMapping->is_corrupt_data = $is_corrupt_data;
        $dataMapping->save();
    }
}
