<?php

namespace App\Services;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Models\KycLog;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;

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
        $this->bridgerEndPoint = config('constants.BRIDGER_ENDPOINT'); // 'https://staging.bridger.lexisnexis.eu/LN.WebServices';
        $this->bridgerClientID = config('constants.BRIDGER_CLIENTID'); // 'AFIALLCAETEST';
        $this->bridgerUserName = config('constants.BRIDGER_USERNAME'); // 'DaniyalS01';
        $this->bridgerPassword = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::BRIDGER_PASSWORD); // 'user@1234@';
        $this->bridgerAPIKey = config('constants.BRIDGER_APIKEY'); // '043b2bb1-2af9-46fe-add5-e6cee1e39259';
    }

    public function getJWTToken()
    {
        $tokenEndPoint = $this->bridgerEndPoint.'/api/Token/Issue';
        $bridgerAuthBasic = base64_encode($this->bridgerClientID.'/'.$this->bridgerUserName.':'.$this->bridgerPassword);
        $bridgerClient = new \GuzzleHttp\Client;
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
                info('Bridger Insight Service - JWT Token Generated');

                return $_return;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $_return['status'] = false;
            $responseErrorCode = $e->getResponse()->getStatusCode();
            logger()->error('Bridger Insight Service - JWT Token Error: '.$responseErrorCode);
        }

        return $_return;
    }

    public function searchAMLResult($bridgerAPIToken, $memberUboDetails, $quoteDetails, $quoteTypeId, $customerType, $loginCustomerEmail)
    {
        if ($bridgerAPIToken['status']) {
            $quoteId = $quoteDetails->id;
            $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
            $amlQuoteUrl = config('constants.APP_URL').'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteDetails->id;
            $bridgerEndPoint = $this->bridgerEndPoint.'/api/Lists/Search';
            $bridgerClient = new \GuzzleHttp\Client;
            $getBasicConfiguration = $this->getBridgerXGBasicConfig();

            switch ($customerType) {
                case CustomerTypeEnum::Individual:
                    $customerOrEntityName = $memberUboDetails['first_name'].(($memberUboDetails['last_name'] == 'NULL' || $memberUboDetails['last_name'] == null) ? '' : ' '.$memberUboDetails['last_name']);
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

            info('Bridger Insight Service - Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType.' - Code: '.$memberUboDetails['code'].' Triggered By: '.$loginCustomerEmail);

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
                        'timeout' => 30,
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

                    info('Bridger Insight Service - Ref-ID: '.$quoteDetails->code.' - Error Email Send to Engineering Team');
                    AMLService::sendAMLErrorEmailtoEngTeam($amlQuoteUrl, $apiResponseMessage, $amlDataForEmail, $getStatusCode);
                } else {
                    if ($getDecodeContents) {
                        // Send Email alert to Compliance team only
                        /*   if (checkPersonalQuotes($quoteType->code) && (! AMLService::isDataMigrated($quoteTypeId, $quoteId))) {
                               $quoteId = AMLService::getPersonalQuoteId($quoteTypeId, $quoteId);
                           } */
                        $quoteRefId = $this->getQuoteCode($quoteType->code, $quoteId);
                        if ($quoteRefId) {
                            // AML Log data inserted into kyc_logs just for BridgerInsight
                            $amlResultCount = 0;
                            if (isset($getDecodeContents->Records[0]->Watchlist)) {
                                $amlResultCount = collect($getDecodeContents->Records[0]->Watchlist->Matches)->filter(function ($value) {
                                    return $value->FalsePositive == false;
                                })->count();
                            }

                            session()->push('amlResponseCheck', $amlResultCount > 0);
                            $kycLogDetails = [
                                'quote_request_id' => $quoteId,
                                'quote_type_id' => $quoteTypeId,
                                'results' => isset($getDecodeContents->Records) ? json_encode($getDecodeContents->Records) : json_encode([]),
                                'results_found' => $amlResultCount,
                                'created_at' => Carbon::now(),
                                'input' => $customerOrEntityName,
                                'match_found' => $amlResultCount > 0 ? 1 : 0,
                                'search_type' => (substr($memberUboDetails['code'], 0, 3) == CustomerTypeEnum::IndividualShort) ? CustomerTypeEnum::Individual : CustomerTypeEnum::Entity,
                                'customer_code' => $memberUboDetails['code'],
                                'decision' => AMLDecisionStatusEnum::ESCALATED,
                            ];

                            if ($amlResultCount == 0) {
                                $kycLogDetails['decision'] = AMLDecisionStatusEnum::PASS;
                            }
                            KycLog::insert($kycLogDetails);
                            info('Bridger Insight Service - Ref-ID: '.$quoteDetails->code.' - AML Screening Potential Matches inserted into kyc_logs table. Total Matches: '.$amlResultCount);

                            if (isset($getDecodeContents->Records)) {
                                AMLService::sendAMLMatchedEmailtoComplianceTeam($amlQuoteUrl, $quoteRefId, $amlResultCount, $customerOrEntityName, $quoteType->text, $loginCustomerEmail);
                                info('Bridger Insight Service - Ref-ID: '.$quoteDetails->code.' - AML Screening Matched Email triggered to Compliance Team. Triggered By: '.$loginCustomerEmail);
                            }
                        }
                    }
                }
            } catch (Exception $exception) {
                logger()->error('Bridger Insight Service - Failed - Ref-ID: '.$quoteDetails->code.' - Error : '.$exception->getMessage());
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
                $additionalInformation = [];
                $dateOfBirth = ($details['dob']) ? explode('-', $details['dob']) : [];
                // $withFullName = isset($details['with_full_name']) && $details['with_full_name'];

                if (($details['nationality']['text'] ?? '') != '') {
                    $additionalInformation[] = ['Type' => 'Citizenship', 'Value' => $details['nationality']['text'] ?? ''];
                }

                if (! empty($dateOfBirth) && array_key_exists(2, $dateOfBirth)) {
                    $additionalInformation[] = ['Type' => 'DOB', 'Date' => [
                        'Day' => $dateOfBirth[2],
                        'Month' => $dateOfBirth[1],
                        'Year' => $dateOfBirth[0],
                    ]];
                }

                $payLoad = array_merge($basicConfig, [
                    'SearchInput' => [
                        'Records' => [
                            [
                                'Entity' => [
                                    'EntityType' => CustomerTypeEnum::Individual,
                                    'Name' => ['Full' => $details['first_name'].' '.$details['last_name']],
                                    // 'Name' => ($withFullName) ?
                                    // ['Full' => $details['first_name'] .' '. $details['last_name']] :
                                    // ['First' => $details['first_name'], 'Last' => $details['last_name']],
                                    'AdditionalInfo' => $additionalInformation,
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

    public function updateDecisionOnLexisNexis($bridgerToken, $request, $decisions)
    {
        $bridgerEndPoint = $this->bridgerEndPoint.'/api/Results/SetRecordState';
        $bridgerClient = new \GuzzleHttp\Client;

        $amlUpdateData = [
            'ClientContext' => [
                'ClientID' => $this->bridgerClientID,
                'UserID' => $this->bridgerUserName,
                'Password' => $this->bridgerPassword,
            ],
            'ResultID' => $request->result_id,
            'State' => [
                'MatchStates' => $decisions,
            ],
            'AddedtoAcceptedList' => false,
            'AlertState' => 'Open',
            'Division' => 'Default Division',
            'AssignedTo' => ['Administrator'],
            'Note' => $request->notes ?? '',

        ];

        // info('Bridger Insight Service - Bridger Decision update API Call - Quote Ref-ID: '.$request->ref_id.' - AML ID: '.($request->aml_id ?? '-').' - Bridger Alert ID: '.($request->result_id ?? '-').' - Decision Update API Payload : '.json_encode($amlUpdateData).' - Triggered by: '.auth()->user()->email);

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

            $getContents = $bridgerRequest->getBody();
            info('Bridger Insight Service - Bridger Decision Updated -  Response: '.json_encode($getContents));

            $response['status'] = 'success';
            $response['message'] = 'Bridger Decision';
        } catch (Exception $exception) {
            $response['status'] = 'error';
            $response['message'] = 'Bridger Decision Update Failed';

            if (str_contains($exception->getMessage(), 'The record was locked')) {
                $response['message'] = str_replace(['}', '"', ']', '\n'], '', explode('{"Message":', $exception->getMessage())[1]) ?? 'The record was locked';
            } else {
                logger()->error('Bridger Insight Service - Failed - Error : '.$exception->getMessage());
            }
        }

        return $response;
    }
}
