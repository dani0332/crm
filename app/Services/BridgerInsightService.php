<?php

namespace App\Services;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\CustomerTypeEnum;
use App\Models\KycLog;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class BridgerInsightService
{
    use GenericQueriesAllLobs;

    private $bridgerEndPoint;
    private $bridgerClientID;
    private $bridgerUserName;
    private $bridgerPassword;
    private $bridgerAPIKey;

    public function __construct()
    {
        $this->bridgerEndPoint = 'https://staging.bridger.lexisnexis.eu/LN.WebServices';
        $this->bridgerClientID = 'AFIALLCAETEST';
        $this->bridgerUserName = 'DaniyalS01';
        $this->bridgerPassword = 'user@1234@';
        $this->bridgerAPIKey = '043b2bb1-2af9-46fe-add5-e6cee1e39259';
    }

    public function getJWTToken()
    {
        $tokenEndPoint = $this->bridgerEndPoint.'/api/Token/Issue';
        $bridgerAuthBasic = base64_encode($this->bridgerClientID.'/'.$this->bridgerUserName.':'.$this->bridgerPassword);
        $bridgerClient = new \GuzzleHttp\Client();
        $_return = ['status' => true];

        try {
            $tokenRequest = $bridgerClient->post(
                $tokenEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$bridgerAuthBasic,
                    ],
                ]
            );
            if ($tokenRequest->getStatusCode() == 200) {
                $getDecodeContents = json_decode($tokenRequest->getBody());
                $_return['response'] = $getDecodeContents->access_token;
                Log::info('Bridger Insight Service - New Token Generated');

                return $_return;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $_return['status'] = false;
            $responseErrorCode = $e->getResponse()->getStatusCode();
            Log::error('Bridger Insight Service - JWT Token Error: '.$responseErrorCode);
        }

        return $_return;
    }

    public function searchAMLResult($bridgerAPIToken, $memberUboDetails, $quoteRequestId, $quoteTypeId, $customerType, $loginCustomerID)
    {
        if ($bridgerAPIToken['status']) {
            $quoteId = $quoteRequestId;
            $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
            $amlQuoteUrl = config('constants.APP_URL').'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteRequestId;
            $bridgerEndPoint = $this->bridgerEndPoint.'/api/Lists/Search';
            $loginUserEmail = auth()->user()->email ?? '';
            $bridgerClient = new \GuzzleHttp\Client();
            $getBasicConfiguration = $this->getBridgerXGBasicConfig();

            switch ($customerType) {
                case CustomerTypeEnum::Individual:
                    $customerOrEntityName = $memberUboDetails['first_name'].' '.$memberUboDetails['last_name'];
                    $amlSearchData = $this->getPayload(CustomerTypeEnum::Individual, $memberUboDetails, $getBasicConfiguration);
                    break;

                case CustomerTypeEnum::Entity:
                    $customerOrEntityName = $memberUboDetails['company_name'];
                    $amlSearchData = $this->getPayload(CustomerTypeEnum::Entity, $memberUboDetails, $getBasicConfiguration);
                    break;

                default:
                    $amlSearchData = [];
                    $customerOrEntityName = '';
            }

            try {
                $bridgerRequest = $bridgerClient->post(
                    $bridgerEndPoint,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer '.$bridgerAPIToken['response'],
                            'X-API-Key' => $this->bridgerAPIKey,
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
                if (! in_array($getStatusCode, $apiSuccessCode)) {
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
                    Log::info('Bridger Insight Service - Error Email Send to Engineering Team');
                    AMLService::sendAMLErrorEmailtoEngTeam($amlQuoteUrl, $apiResponseMessage, $amlDataForEmail, $getStatusCode);
                } else {
                    if ($getDecodeContents) {
                        // Send Email alert to Compliance team only
                        if (checkPersonalQuotes($quoteType->code) && (AMLService::isDataMigrated($quoteTypeId, $quoteId))) {
                            $quoteId = AMLService::getPersonalQuoteId($quoteTypeId, $quoteId);
                        }
                        $quoteRefId = $this->getQuoteCode($quoteType->code, $quoteId);
                        if ($quoteRefId) {
                            // AML Log data inserted into kyc_logs just for BridgerInsight
                            session()->push('amlResponseCheck', isset($getDecodeContents->Records));
                            $kycLogDetails = [
                                'quote_request_id' => $quoteId,
                                'quote_type_id' => $quoteTypeId,
                                'results' => isset($getDecodeContents->Records) ? json_encode($getDecodeContents->Records) : json_encode([]),
                                'results_found' => isset($getDecodeContents->Records[0]) ? count($getDecodeContents->Records[0]->Watchlist->Matches) : 0,
                                'created_at' => Carbon::now(),
                                'input' => $customerOrEntityName,
                                'match_found' => isset($getDecodeContents->Records) ? 1 : 0,
                                'search_type' => $customerType,
                                'customer_code' => $memberUboDetails['code'],
                            ];

                            if (! isset($getDecodeContents->Records)) {
                                $kycLogDetails['decision'] = AMLDecisionStatusEnum::PASS;
                            }

                            KycLog::insert($kycLogDetails);
                            Log::info('Bridger Insight Service - KYC Log data inserted');
                            AMLService::sendAMLMatchedEmailtoComplianceTeam($amlQuoteUrl, $quoteRefId, json_encode($getDecodeContents->Records ?? ['Records' => 'Not Found']), $customerOrEntityName, $quoteType->text, $loginCustomerID);
                            Log::info('Bridger Insight Service - AML Matched Email triggered to Compliance Team');
                        }
                    }
                }
            } catch (Exception $exception) {
                Log::error('Bridger Insight Service - Failed - Error : '.$exception->getMessage());
            }
        }
    }

    private function getBridgerXGBasicConfig()
    {
        return [
            'SearchConfiguration' => [
                'AssignResultTo' => [
                    'Division' => 'Default Division',
                    'EmailNotification' => false,
                    'Type' => 'Role',
                    'RolesOrUsers' => ['Administrator', 'Compliance Officer', 'Junior Compliance Officer'],
                ],
                'WriteResultsToDatabase' => true,
                'PredefinedSearchName' => 'List Screening',
            ],
        ];
    }

    private function getPayload($customerType, $details, $basicConfig)
    {
        $payLoad = [];
        switch ($customerType) {
            case CustomerTypeEnum::Individual:
                $dateOfBirth = explode('-', $details['dob']);
                $payLoad = array_merge($basicConfig, [
                    'SearchInput' => [
                        'Records' => [
                            [
                                'Entity' => [
                                    'EntityType' => CustomerTypeEnum::Individual,
                                    'Name' => ['First' => $details['first_name'], 'Last' => $details['last_name']],
                                    'AdditionalInfo' => [
                                        ['Type' => 'DOB', 'Date' => ['Day' => $dateOfBirth[2], 'Month' => $dateOfBirth[1], 'Year' => $dateOfBirth[0]]],
                                        ['Type' => 'Citizenship', 'Value' => isset($details['nationality']) ? $details['nationality']['text'] : ''],
                                    ],
                                    'IDs' => [
                                        ['Type' => 'Account', 'Number' => $details['code']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                break;
            case CustomerTypeEnum::Entity:
                $payLoad = array_merge($basicConfig, [
                    'SearchInput' => [
                        'Records' => [
                            [
                                'Entity' => [
                                    'EntityType' => CustomerTypeEnum::Business,
                                    'Name' => ['Full' => $details['company_name']],
                                    'IDs' => [
                                        ['Type' => 'Account', 'Number' => $details['code']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                break;

            default: return $payLoad;
        }

        return $payLoad;
    }

    public function updateDecisionOnLexisNexis($bridgerToken, $resultId, $decision)
    {
        $bridgerEndPoint = $this->bridgerEndPoint.'/api/Results/SetRecordState';
        $bridgerClient = new \GuzzleHttp\Client();

        $amlUpdateData = [
            'ClientContext' => [
                'ClientID' => $this->bridgerClientID,
                'UserID' => $this->bridgerUserName,
                'Password' => $this->bridgerPassword,
            ],
            'ResultID' => $resultId,
            'State' => [
                'Note' => 'Testing Decision Update',
            ],
        ];

        try {
            $bridgerRequest = $bridgerClient->post(
                $bridgerEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer '.$bridgerToken['response'],
                        'X-API-Key' => $this->bridgerAPIKey,
                    ],
                    'body' => json_encode($amlUpdateData),
                    'timeout' => 10,
                ]
            );

            $getStatusCode = $bridgerRequest->getStatusCode();
            $getContents = $bridgerRequest->getBody();
            $getDecodeContents = json_decode($getContents);

            Log::info('Bridger Insight Service - AML Decision Update API Call Response : '.json_encode($getContents));
        } catch (Exception $exception) {
            Log::error('Bridger Insight Service - Failed - Error : '.$exception->getMessage());
        }
    }
}
