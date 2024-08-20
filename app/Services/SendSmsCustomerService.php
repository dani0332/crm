<?php

namespace App\Services;

use App\Models\Customer;
use Exception;

class SendSmsCustomerService extends BaseService
{
    private $applicationStorageService;

    public function __construct(ApplicationStorageService $applicationStorageService)
    {
        $this->applicationStorageService = $applicationStorageService;
    }

    public function sendMAInviteSMS($email, $mobile_no)
    {
        if (! $mobile_no) {
            info('sendMAInviteSMS - Error - No Mobile Number for Customer');

            return false;
        }
        $isSmsTestingEnabled = $this->applicationStorageService->getValueByKey('IS_MA_SMS_AFIA_TESTING_ENABLE');

        if ($isSmsTestingEnabled && ! $this->isAfiaEmail($email)) {
            return false;
        }

        $customerMobile = str_replace([' ', '-'], '', $mobile_no);
        if (preg_match('/^(?:971|00971|\+971|0)?(?:50|51|52|54|55|56|58)\d{7}$/', $customerMobile)) {
            $mobileNumber = '971'.substr($customerMobile, -9);
        } else {
            $mobileNumber = null;
        }

        if (! $mobileNumber) {
            info('Invalid mobile number: '.$customerMobile.' | email: '.$email.' | class: '.get_class());

            return false;
        }

        $smsMessage = 'Welcome to the InsuranceMarket.ae family! Avail offers from over 100 brands on the myAlfred app. Download the app and use email address to sign up! optoutMA4741';
        try {
            $smsEndpoint = config('constants.SMS_ENDPOINT');
            $smsSender = config('constants.SMS_SENDER_ID');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $client = new \GuzzleHttp\Client;
            $clientRequest = $client->request('POST', $smsEndpoint, ['query' => [
                'username' => $smsUsername,
                'password' => $smsPassword,
                'senderid' => $smsSender,
                'to' => $customerMobile,
                'text' => $smsMessage,
                'type' => 'text',
            ]]);

            $responseCode = $clientRequest->getStatusCode();

            info('sendMAInviteSMS - Sent - Response: '.$responseCode.' | mobile: '.$customerMobile.' | email: '.$email);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            info('sendMAInviteSMS - Error - Response Code: '.$responseCode.' | mobile: '.$customerMobile.' | email: '.$email.' | class: '.get_class());
        }

        return $responseCode;
    }

    public function getShortUrl($url)
    {
        try {
            $smsEndpoint = config('constants.SMS_URL_SHORTNER_ENDPOINT');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $response = (new \GuzzleHttp\Client)->post($smsEndpoint, [
                'json' => [
                    'username' => $smsUsername,
                    'password' => $smsPassword,
                    'long_url' => $url,
                    'type' => 'unique',
                ],
                'timeout' => 10000,
            ]);

            return json_decode($response->getBody()->getContents())->short_url;
        } catch (\Exception $ex) {
            $response = $ex->getCode().' '.$ex->getMessage();
            info($response);

            return json_encode($response);
        }
    }

    private function isAfiaEmail($email)
    {
        $isAfiaEmail = false;

        $acceptedDomains = ['afia.ae', 'insurancemarket.ae'];

        if (in_array(substr($email, strrpos($email, '@') + 1), $acceptedDomains)) {
            $isAfiaEmail = true;
        }

        return $isAfiaEmail;
    }

    /**
     * @return int|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSMS(mixed $customerMobile, string $smsMessage, Customer $customer, ?string $inviteCode = null): mixed
    {
        try {
            $smsEndpoint = config('constants.SMS_ENDPOINT');
            $smsSender = config('constants.SMS_SENDER_ID');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $client = new \GuzzleHttp\Client;
            $query = [
                'username' => $smsUsername,
                'password' => $smsPassword,
                'senderid' => $smsSender,
                'to' => $customerMobile,
                'text' => $smsMessage,
                'type' => 'text',
            ];
            if ($inviteCode !== null) {
                $query['invite_code'] = $inviteCode;
            }
            $clientRequest = $client->request('POST', $smsEndpoint, ['query' => $query]);

            $responseCode = $clientRequest->getStatusCode();

            $logMessage = 'sendSMS - Sent - Response: '.$responseCode.' | mobile: '.$customerMobile.' | email: '.$customer->email;
            if ($inviteCode !== null) {
                $logMessage .= ' | Invite Code: '.$inviteCode;
            }
            info($logMessage);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $logMessage = 'sendSMS - Error - Response Code: '.$responseCode.' | mobile: '.$customerMobile.' | email: '.$customer->email;
            if ($inviteCode !== null) {
                $logMessage .= ' | Invite Code: '.$inviteCode;
            }
            $logMessage .= ' | class: '.get_class();
            info($logMessage);
        }

        return $responseCode;
    }
}
