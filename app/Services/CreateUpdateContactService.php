<?php

namespace App\Services;

use Config;

class CreateUpdateContactService extends BaseService
{
	public static function contactCreateUpdate($listId, $firstName, $lastName, $email, $signupLink)
    {
        $endPointUrl = Config::get('constants.SIB_CONTACTS_API_ENDPOINT_URL');
        $apiKey = Config::get('constants.SENDINBLUE_KEY');

        $customerData = json_encode([
            "email" => $email,
            "attributes" => array(
                "FIRSTNAME" => $firstName,
                "LASTNAME" => $lastName,
                "WEBSITE" => $signupLink),
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
