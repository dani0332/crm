<?php

namespace App\Services;

use Config;

class CreateUpdateContactService extends BaseService
{
    public static function contactCreateUpdate($listId, $firstName, $lastName, $email, $signupLink, $data = [])
    {
        $endPointUrl = Config::get('constants.SIB_CONTACTS_API_ENDPOINT_URL');
        $apiKey = Config::get('constants.SENDINBLUE_KEY');

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
                'CDBID' => isset($data['cbdid']) ? $data['cbdid'] : null,
                'QUOTEPLANLINK' => isset($data['link']) ? $data['link'] : null,
                'HEALTH_WEBHOOK_URL' => null,
                'ADVISORLANDLINE' => isset($data['advisorLandline']) ? $data['advisorLandline'] : null,
            ],
            'listIds' => [(int) $listId],
            'updateEnabled' => true,
        ]);
        $clientExtendSubscription = new \GuzzleHttp\Client();

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
                ]
            );

            $apiResponse = $requestExtendSubscription->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $apiResponse = $e->getResponse()->getStatusCode();
        }

        return $apiResponse;
    }
}
