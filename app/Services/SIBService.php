<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SIBService extends BaseService
{
    public static function contactCreateUpdate($listId, $firstName, $lastName, $email, $signupLink, $data = [])
    {
        info('Sync Contact SIB - CDBID: '.$data['cdbid'].' - Data: '.json_encode($data));
        $endPointUrl = config('constants.SIB_CONTACTS_API_ENDPOINT_URL');
        $apiKey = config('constants.SENDINBLUE_KEY');

        $customerData = json_encode([
            'email' => $email,
            'attributes' => [
                'FIRSTNAME' => $firstName,
                'LASTNAME' => $lastName,
                'WEBSITE' => $signupLink,
                'ADVISOREMAIL' => isset($data['advisorEmail']) ? $data['advisorEmail'] : null,
                'ADVISORMOBILE' => isset($data['advisorMobile']) ? $data['advisorMobile'] : null,
                'CUSTOMERNAME' => isset($data['customerName']) ? $data['customerName'] : null,
                'ADVISORNAME' => isset($data['advisorName']) ? $data['advisorName'] : null,
                'LEAD_STATUS' => isset($data['leadStatus']) ? $data['leadStatus'] : null,
                'CDBID' => isset($data['cdbid']) ? $data['cdbid'] : null,
                'QUOTEPLANLINK' => isset($data['link']) ? $data['link'] : null,
                'HEALTH_WEBHOOK_URL' => null,
                'ADVISORLANDLINE' => isset($data['advisorLandline']) ? $data['advisorLandline'] : null,
            ],
            'listIds' => [(int) $listId],
            'updateEnabled' => true,
        ]);
        $clientExtendSubscription = new \GuzzleHttp\Client();
        $apiResponse = null;
        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $endPointUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'api-key' => $apiKey,
                    ],
                    'body' => $customerData,
                    'timeout' => 10000,
                ]
            );

            $apiResponse = $requestExtendSubscription->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            Log::error('Sync Contact SIB - Error: '.$exception->getMessage());
        }

        return $apiResponse;
    }
}
