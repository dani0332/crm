<?php

namespace App\Services;

use App\Models\MyAlFredUser;

class CustomerExtendSubscriptionService extends BaseService
{
    public static function extendCustomerSubscription($customerId)
    {
        $extendSubscriptionEndPoint = config('constants.BERLIN_API_ENDPOINT').'/auth/extend-subscription';
        $extendSubscriptionUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $extendSubscriptionPassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $customerToken = MyAlFredUser::select('signup_url', 'code')->where('customer_id', $customerId)->latest()->first();

        $customerDataArr = json_encode([
            'token' => $customerToken->code,
            'isToken' => isset($customerToken->signup_url) ? true : false,
        ]);
        info('customerDataArr: '.$customerDataArr);
        info('signup_url: '.$customerToken->signup_url);
        info('code: '.$customerToken->code);

        $magicUrlGeneratauthBasic = base64_encode($extendSubscriptionUserName.':'.$extendSubscriptionPassword);
        $clientExtendSubscription = new \GuzzleHttp\Client();

        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $extendSubscriptionEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$magicUrlGeneratauthBasic,
                    ],
                    'body' => $customerDataArr,
                ]
            );

            $apiResponse = $requestExtendSubscription->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $apiResponse = $e->getResponse()->getStatusCode();
        }

        return $apiResponse;
    }
}
