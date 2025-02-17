<?php

namespace App\Services;

class HttpRequestService extends BaseService
{
    public function processRequest($data, $creds)
    {
        $authBasic = base64_encode($creds['apiUserName'].':'.$creds['apiPassword']);

        $kenClient = new \GuzzleHttp\Client;
        try {
            $kenRequest = $kenClient->post(
                $creds['apiEndPoint'],
                [
                    'headers' => [
                        'Content-Type' => 'application/json', 'Accept' => 'application/json',
                        'x-api-token' => $creds['apiToken'],
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'body' => json_encode($data),
                    'timeout' => $creds['apiTimeout'],
                ]
            );
            $statusCode = $kenRequest->getStatusCode();

            return $statusCode;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = json_decode((string) $e->getResponse()->getBody());

            if (isset($response->error)) {
                $response = $response->error;
            }
            if (isset($response->msg)) {
                $response = $response->msg;
            }

            return $response;
        }
    }

    public function executeGetPlansApi($id, mixed $getLatestRating, mixed $isRenewalSort, mixed $isDisabledEnabled, $quoteType = '', ?bool $allowUpdate = null): mixed
    {
        // Set model name
        $modelName = 'CarQuote';
        $type = 'Car';

        if (! empty($quoteType) && $quoteType != '') {
            // check in checkPersonalQuotes to access PersonalQuote Model
            $modelName = checkPersonalQuotes(ucfirst($quoteType)) ? 'PersonalQuote' : 'CarQuote';
            $type = $quoteType;
        }

        $model = '\\App\\Models\\'.$modelName;
        // Get the Quote UUID
        $quoteUuId = $model::where('uuid', '=', $id)->value('uuid');

        // Configuration values
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-'.lcfirst($type).'-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);
        // Prepare the request data
        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'getLatestRating' => $getLatestRating,
            'lang' => 'en',
            'url' => strval(url()->current()),
            'ipAddress' => request()->ip(),
            'userAgent' => request()->header('User-Agent'),
            'userId' => strval(auth()->id()),
            'filters' => [
                [
                    'field' => 'isRenewalSort',
                    'value' => $isRenewalSort,
                ],
                [
                    'field' => 'isDisabled',
                    'value' => $isDisabledEnabled,
                ],
            ],
            'callSource' => 'imcrm',
        ];

        if (! is_null($allowUpdate)) {
            $plansDataArr['allowUpdate'] = $allowUpdate;
        }

        $client = new \GuzzleHttp\Client;
        try {
            // Make the API request
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'body' => json_encode($plansDataArr),
                    'timeout' => $plansApiTimeout,
                ]
            );

            // Check the response status code
            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                // Parse and return the response
                $getContents = $kenRequest->getBody();
                $getdecodeContents = json_decode($getContents);

                return $getdecodeContents;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            // add info for error and exception along with stack trace
            info('exception occurred in quote plans call with error : '.$e->getMessage());
            info('exception occurred in quote plans call with error stack as  : '.$e->getTraceAsString());
            // Handle exceptions and errors
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'Quote unavailable for the selected location and region. Please call 800 ALFRED.';
            }

            return $responseBodyAsString;
        }
    }

    public function getPlans($id, $getLatestRating, $isRenewalSort = false, $isDisabledEnabled = false, $quoteType = '', ?bool $allowUpdate = null)
    {
        $quotePlans = $this->executeGetPlansApi($id, $getLatestRating, $isRenewalSort, $isDisabledEnabled, $quoteType, allowUpdate: $allowUpdate);

        // Check if the $quotePlans object has a message property
        if (isset($quotePlans->message) && $quotePlans->message != '') {
            return [];
        }

        // Extract the plans if they exist, otherwise return an empty array
        return isset($quotePlans->quotes->plans) ? $quotePlans->quotes->plans : [];
    }
}
