<?php

namespace App\Services;

use Config;

class CreateUpdateContactService extends BaseService
{
	public static function contactCreateUpdate($listId, $firstName, $lastName, $email, $signupLink, $data=[])
    {
        $endPointUrl = Config::get('constants.SIB_CONTACTS_API_ENDPOINT_URL');
        $apiKey = Config::get('constants.SENDINBLUE_KEY');

        $customerData = json_encode([
            "email" => $email,
            "attributes" => array(
                "FIRSTNAME" => $firstName,
                "LASTNAME" => $lastName,
                "WEBSITE" => $signupLink,
                "ADVISOREMAIL" => isset($data['advisorEmail']) ? $data['advisorEmail'] : Null,
                "ADVISORMOBILE" => isset($data['advisorMobile']) ? $data['advisorMobile'] : Null,
                "CUSTOMERNAME" => isset($data['customerName']) ? $data['customerName'] : Null,
                "ADVISORNAME" => isset($data['advisorName']) ? $data['advisorName'] : Null,
                "LEAD_STATUS" => isset($data['lead_status']) ? $data['lead_status'] : Null,
                "CDBID" =>isset($data['cbdid']) ? $data['cbdid'] : Null,
            ),
            "listIds" => [(int)$listId],
            "updateEnabled" => true,
        ]);

        $clientExtendSubscription = new \GuzzleHttp\Client();

        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $endPointUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'api-key' => $apiKey
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
