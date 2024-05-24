<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\MAWelcomeJob;
use App\Models\MyAlFredUser;
use Illuminate\Support\Facades\Log;

class BerlinService extends BaseService
{
    private $berlinEndpoint;
    private $berlinUserName;
    private $berlinAuthPassword;
    private $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->berlinEndpoint = config('constants.BERLIN_API_ENDPOINT');
        $this->berlinUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $this->berlinAuthPassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');
        $this->customerService = $customerService;
    }

    public function getCustomerInviteCode()
    {
        $inviteCodeGeneratauthBasic = base64_encode($this->berlinUserName.':'.$this->berlinAuthPassword);
        $clientBerlin = new \GuzzleHttp\Client();

        try {
            $berlinRequest = $clientBerlin->post(
                $this->berlinEndpoint.'/auth/generate-code',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$inviteCodeGeneratauthBasic,
                    ],
                    'timeout' => 10,
                ]
            );

            if ($berlinRequest->getStatusCode() == 200) {
                $getdecodeContents = json_decode($berlinRequest->getBody());
                $getResponseInviteCode = $getdecodeContents->data->inviteCode;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $responseErrorCode = $e->getResponse()->getStatusCode();
            Log::error('Berlin Service - InviteCodeService Error: '.$responseErrorCode);
        }

        if (isset($getResponseInviteCode)) {
            $apiResponse = $getResponseInviteCode;
        } else {
            if (isset($responseErrorCode)) {
                $apiResponse = $responseErrorCode;
            }
        }

        return $apiResponse;
    }

    public static function getCustomerWeUrl()
    {
        $magicUrlGenerateEndPoint = config('constants.BERLIN_API_ENDPOINT').'/auth/generate-url';
        $magicUrlGenerateUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $magicUrlGeneratePassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $magicUrlGeneratauthBasic = base64_encode($magicUrlGenerateUserName.':'.$magicUrlGeneratePassword);
        $clientBerlin = new \GuzzleHttp\Client();

        try {
            $berlinRequest = $clientBerlin->post(
                $magicUrlGenerateEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$magicUrlGeneratauthBasic,
                    ],
                ]
            );

            if ($berlinRequest->getStatusCode() == 200) {
                $getdecodeContents = json_decode($berlinRequest->getBody());
                $getResponseUrl = $getdecodeContents->data->url;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $responseErrorCode = $e->getResponse()->getStatusCode();
        }

        if (isset($getResponseUrl)) {
            $apiResponse = $getResponseUrl;
        } else {
            if (isset($responseErrorCode)) {
                $apiResponse = $responseErrorCode;
            }
        }

        return $apiResponse;
    }

    public function extendCustomerSubscription($customerId, $customerEmail, $source, $tag)
    {
        $customer = MyAlFredUser::select('signup_url', 'code')->where('customer_id', $customerId)->latest()->first();

        if (! $customer) {
            $customer = $this->customerService->getCustomerById($customerId);
            $customer->code = null;
        }

        if (! $customer) {
            Log::error('Berlin Service - extendCustomerSubscription Error: MyAlFredUser not found - Customer ID: '.$customerId);

            return false;
        }

        $isToken = strlen($customer->code) > 8;
        $hasToken = ! is_null($customer->code);

        $customerDataArr = [];

        if ($hasToken) {
            $customerDataArr[$isToken ? 'token' : 'otp'] = $customer->code;
        }

        $customerDataArr['email'] = $customerEmail;
        $customerDataJson = json_encode($customerDataArr);
        $magicUrlGeneratauthBasic = base64_encode($this->berlinUserName.':'.$this->berlinAuthPassword);
        $clientExtendSubscription = new \GuzzleHttp\Client();

        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $this->berlinEndpoint.'/internal/extend-subscription',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$magicUrlGeneratauthBasic,
                    ],
                    'body' => $customerDataJson,
                    'timeout' => 10,
                ]
            );

            $statusCode = $requestExtendSubscription->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $statusCode = $e->getResponse()->getStatusCode();

            $errorData = json_decode($e->getResponse()->getBody()->getContents(), true);

            if ($errorData['code'] == 'CUSTOMER_NOT_FOUND') {
                $customer = $this->customerService->getCustomerByEmail($customerEmail);
                Log::warning('extendCustomerSubscription Customer Id: '.$customerId.' Customer Email: '.$customerEmail.' Error Code: '.$errorData['code'].' Customer not exist so cannot proceed to extend subscription, sending signup email to customer. API Message: '.$errorData['message']);
                MAWelcomeJob::dispatchUnless(
                    isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)),
                    $customer->first_name,
                    $customer->last_name,
                    $customer->email,
                    $customer->mobile_no,
                    $source,
                    $tag
                );
            } else {
                Log::error('Berlin Service - extendCustomerSubscription - Customer ID: '.$customerId.' - Status Code: '.$statusCode.' - '.$e->getMessage());
            }
        }

        return $statusCode;
    }
}
