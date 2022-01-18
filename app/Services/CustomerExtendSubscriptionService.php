<?php

namespace App\Services;

use App\Models\MyAlFredUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CustomerExtendSubscriptionService extends BaseService
{
	public static function extendCustomerSubscription($customerId)
    {
        $extendSubscriptionEndPoint = Config::get('constants.BERLIN_API_ENDPOINT').'/auth/extend-subscription';
        $extendSubscriptionUserName = Config::get('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $extendSubscriptionPassword = Config::get('constants.BERLIN_BASIC_AUTH_PASSWORD');

        // order by created_at asc first
        $customerToken = MyAlFredUser::select('code')->where('customer_id', '=', $customerId)->orderBy('created_at','asc')->first();
        //$customerToken = 'ZGU1YTI1NWItMDk2OC00ZGFkLWJkZjQtZDA3M2I2Nzg1NWMy';

        $customerDataArr = json_encode([
            "token" => $customerToken,
        ]);

        $magicUrlGeneratauthBasic = base64_encode($extendSubscriptionUserName . ":" . $extendSubscriptionPassword);
        $clientExtendSubscription = new \GuzzleHttp\Client();

        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $extendSubscriptionEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic ' . $magicUrlGeneratauthBasic
                    ],
                    'body' => $customerDataArr,
                ]
            );

            $apiResponse = $requestExtendSubscription->getStatusCode();

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $apiResponse = $e->getResponse()->getStatusCode();
            //$apiResponse = $e->getResponse()->getBody()->getContents();
        }

        // if response code is 422
        if($apiResponse == 422) {
            // Papertrail log: Customer trying to extend subscription but not exist in myAflred with customer email token
        }
        dd($apiResponse);
        return $apiResponse;
    }
}
