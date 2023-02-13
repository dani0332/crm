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

    public function sendMAInviteSMS(Customer $customer, $inviteCode)
    {
        if (! $customer->mobile_no) {
            info('sendMAInviteSMS - Error - No Mobile Number for Customer');

            return false;
        }
        $isSmsTestingEnabled = $this->applicationStorageService->getValueByKey('IS_MA_SMS_AFIA_TESTING_ENABLE');

        if ($isSmsTestingEnabled && ! $this->isAfiaEmail($customer->email)) {
            return false;
        }

        $customerMobile = str_replace([' ', '-'], '', $customer->mobile_no);
        if (preg_match('/^(?:971|00971|\+971|0)?(?:50|51|52|54|55|56|58)\d{7}$/', $customerMobile)) {
            $mobileNumber = '971'.substr($customerMobile, -9);
        } else {
            $mobileNumber = null;
        }

        if (! $mobileNumber) {
            info('Invalid mobile number: '.$customerMobile.' | email: '.$customer->email.' | class: '.get_class());

            return false;
        }

        $smsMessage = 'Welcome to the InsuranceMarket.ae family! Avail offers from over 100 brands on the myAlfred app. Download the app and use code '.$inviteCode.' to sign up! optoutMA4741';
        try {
            $smsEndpoint = config('constants.SMS_ENDPOINT');
            $smsSender = config('constants.SMS_SENDER_ID');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->request('POST', $smsEndpoint, ['query' => [
                'username' => $smsUsername,
                'password' => $smsPassword,
                'senderid' => $smsSender,
                'to' => $customerMobile,
                'text' => $smsMessage,
                'type' => 'text',
            ]]);

            $responseCode = $clientRequest->getStatusCode();

            info('sendMAInviteSMS - Sent - Response: '.$responseCode.' | mobile: '.$customerMobile.' | email: '.$customer->email.' | Invite Code: '.$inviteCode);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            info('sendMAInviteSMS - Error - Response Code: '.$responseCode.' | mobile: '.$customerMobile.' | email: '.$customer->email.' | Invite Code: '.$inviteCode.' | class: '.get_class());
        }

        return $responseCode;
    }

    public function getShortUrl($url)
    {
        try {
            $smsEndpoint = config('constants.SMS_URL_SHORTNER_ENDPOINT');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $body = json_encode([
                'username' => $smsUsername,
                'password' => $smsPassword,
                'long_url' => $url,
                'type' => 'unique',
            ], JSON_UNESCAPED_SLASHES);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $smsEndpoint,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $response = json_decode($clientRequest->getBody()->getContents());
            $response = $response->short_url;
        } catch (Exception $ex) {
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            info($response);
        }

        return $response;
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
}
