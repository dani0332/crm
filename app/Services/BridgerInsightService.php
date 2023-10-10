<?php

namespace App\Services;

use App\Enums\CustomerTypeEnum;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Config;
use Exception;
use Illuminate\Support\Facades\Log;

class BridgerInsightService
{
    use GenericQueriesAllLobs;
    private $bridgerEndPoint;
    private $bridgerClientID;
    private $bridgerUserName;
    private $bridgerPassword;

    public function __construct()
    {
        $this->bridgerEndPoint = 'https://staging.bridger.lexisnexis.eu/LN.WebServices';
        $this->bridgerClientID = 'AFIALLCAETEST';
        $this->bridgerUserName = 'DaniyalS01';
        $this->bridgerPassword = 'user@1234@';
    }

    public function getJWTToken()
    {
        $this->bridgerEndPoint .= '/api/Token/Issue';
        $bridgerAuthBasic = base64_encode($this->bridgerClientID . '/' . $this->bridgerUserName . ':' . $this->bridgerPassword);
        $bridgerClient = new \GuzzleHttp\Client();
        $_return = ['status' => true];

        try {
            $tokenRequest = $bridgerClient->post(
                $this->bridgerEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$bridgerAuthBasic,
                    ]
                ]
            );
            if ($tokenRequest->getStatusCode() == 200) {
                $getDecodeContents = json_decode($tokenRequest->getBody());
                $_return['response'] = $getDecodeContents->access_token;

                return $_return;
            }

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $_return['status'] = false;
            $responseErrorCode = $e->getResponse()->getStatusCode();
            Log::error('Bridger Insight Service - JWT Token Error: '.$responseErrorCode);
        }

        return $_return;
    }

    public function searchAMLResult($memberUboDetails, $quoteRequestId, $quoteTypeId, $customerType)
    {
//        $getAMLToken = $this->getJWTToken();
//        if ($getAMLToken['status']) {
        if (true) {
            $quoteId = $quoteRequestId;
            $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
            $amlQuoteUrl = Config::get('constants.APP_URL') . '/kyc/aml/' . $quoteTypeId . '/details/' . $quoteRequestId;
            $this->bridgerEndPoint .= '/api/Lists/Search';
            $bridgerClient = new \GuzzleHttp\Client();

            switch ($customerType){
                case CustomerTypeEnum::Individual:
                    $customerOrEntityName = $memberUboDetails['first_name']. ' ' . $memberUboDetails['last_name'];
                    $dateOfBirth = explode('-', $memberUboDetails['dob']);
                    $amlSearchData = [
                        'SearchInput' => [
                            'Records' => [
                                'Entity' => [
                                    'EntityType' => CustomerTypeEnum::Individual,
                                    'Name' => [
                                        'First' => $memberUboDetails['first_name'],
                                        'Last' => $memberUboDetails['last_name']
                                    ],
                                    'AdditionalInfo' => [
                                        [
                                            'Type' => 'DOB',
                                            'Date' => [ 'Day' => $dateOfBirth[2], 'Month' => $dateOfBirth[1], 'Year' => $dateOfBirth[0]]
                                        ],
                                        [
                                            'Type' => 'Citizenship',
                                            'Value' => $memberUboDetails?->nationality?->text ?? ''
                                        ]
                                    ],
                                    'IDs' => [[
                                        'Type' => 'Account',
                                        'Number' => $memberUboDetails->code,
                                    ]]
                                ]
                            ]
                        ]
                    ];
                    break;

                case CustomerTypeEnum::Entity:
                    $customerOrEntityName = 'Company Name';
                    $amlSearchData = [
                        'SearchInput' => [
                            'Records' => [
                                'Entity' => [
                                    'EntityType' => CustomerTypeEnum::Business,
                                    'Name' => 'Company Name',
                                    "IDs" => [
                                        'Number' => '345433',
                                        'Type' => 'Account'
                                    ]
                                ]
                            ]
                        ]
                    ];
                    break;

                default: $amlSearchData = []; $customerOrEntityName = '';
            }

            dd($amlSearchData);

            try {
                $bridgerRequest = $bridgerClient->post(
                    $this->bridgerEndPoint,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'X-API-Key' => $getAMLToken['response'],
                        ],
                        'body' => json_encode($amlSearchData),
                        'timeout' => 10,
                    ]
                );

                $getStatusCode = $bridgerRequest->getStatusCode();
                $getContents = $bridgerRequest->getBody();
                $getDecodeContents = json_decode($getContents);

                // Checking if the API call successful
                $apiSuccessCode = [201, 200];
                if (!in_array($getStatusCode, $apiSuccessCode)) {
                    // Send Error Email alert to Engineering Team
                    $amlDataForEmail =
                    $apiResponseMessage = '';
                    if (is_array($getDecodeContents) || is_object($getDecodeContents)) {
                        foreach ($getDecodeContents as $key1 => $value1) {
                            $apiResponseMessage .= $key1.': '.$value1;
                            $apiResponseMessage .= '<pre>';
                        }
                    }

                    foreach ($memberUboDetails as $key => $value) {
                        $amlDataForEmail .= $key.': '.$value;
                        $amlDataForEmail .= '<pre>';
                    }
                    AMLService::sendAMLErrorEmailtoEngTeam($amlQuoteUrl, $apiResponseMessage, $amlDataForEmail, $getStatusCode);
                } else {
                    if ($getDecodeContents) {
                        // Send Email alert to Compliance team only
                        if (checkPersonalQuotes($quoteType->code) && (AMLService::isDataMigrated($quoteTypeId, $quoteId))) {
                            $quoteId = AMLService::getPersonalQuoteId($quoteTypeId, $quoteId);
                        }
                        $quoteRefId = $this->getQuoteCode($quoteType->code, $quoteId);
                        if ($quoteRefId) {
                            AMLService::sendAMLMatchedEmailtoComplianceTeam($amlQuoteUrl, $quoteRefId, $getDecodeContents, $customerOrEntityName, $quoteType->text);
                        }
                    }
                }
            } catch (Exception $exception) {
                Log::error('Bridger Insight Service - Failed - Error : '.$exception->getMessage());
            }
        }
    }
}
