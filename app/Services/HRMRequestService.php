<?php

namespace App\Services;

use App\Services\Logger\LoggerService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HRMRequestService
{
    /**
     * Get employee codes by email addresses
     *
     * @param  array  $emails  Array of email addresses
     * @return array|false Returns employee data array on success, false on failure
     */
    public static function getEmployeeCodes(array $emails)
    {
        try {
            LoggerService::info(self::class.'::getEmployeeCodes - Starting employee codes request', [
                'emails_count' => count($emails),
                'emails' => $emails,
            ]);

            $apiEndPoint = config('constants.HRM_API_ENDPOINT').'/v1/employees/codes';
            $apiUsername = config('constants.HRM_API_USERNAME');
            $apiPassword = config('constants.HRM_API_PASSWORD');
            $apiTimeout = config('constants.HRM_API_TIMEOUT', 30);

            if (empty($apiEndPoint) || empty($apiUsername) || empty($apiPassword)) {
                LoggerService::error(self::class.'::getEmployeeCodes - Missing API configuration');

                return false;
            }

            $payload = [
                'emails' => $emails,
            ];

            $client = new Client;
            $response = $client->post(
                $apiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'auth' => [$apiUsername, $apiPassword],
                    'body' => json_encode($payload),
                    'timeout' => $apiTimeout,
                ]
            );

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $decodedResponse = json_decode($responseBody, true);

            LoggerService::info(self::class.'::getEmployeeCodes - API response received', [
                'status_code' => $statusCode,
                'response_status' => $decodedResponse['status'] ?? null,
                'message' => $decodedResponse['message'] ?? null,
                'data_count' => isset($decodedResponse['data']) ? count($decodedResponse['data']) : 0,
            ]);

            if ($statusCode === 200) {
                if (isset($decodedResponse['status']) && $decodedResponse['status'] === true) {
                    LoggerService::info(self::class.'::getEmployeeCodes - Successfully retrieved employee codes');

                    return $decodedResponse['data'] ?? [];
                } else {
                    LoggerService::warning(self::class.'::getEmployeeCodes - API returned unsuccessful status', [
                        'response' => $decodedResponse,
                    ]);

                    return false;
                }
            } else {
                LoggerService::error(self::class.'::getEmployeeCodes - API returned non-200 status code', [
                    'status_code' => $statusCode,
                    'response' => $decodedResponse,
                ]);

                return false;
            }

        } catch (RequestException $e) {
            LoggerService::error(self::class.'::getEmployeeCodes - HTTP request exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'emails' => $emails,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;

        } catch (Exception $e) {
            LoggerService::error(self::class.'::getEmployeeCodes - General exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'emails' => $emails,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get employee code for a single email address
     *
     * @param  string  $email  Single email address
     * @return array|false Returns employee data on success, false on failure
     */
    public static function getEmployeeCode(string $email)
    {
        $result = self::getEmployeeCodes([$email]);

        if ($result !== false && is_array($result) && count($result) > 0) {
            return $result[0];
        }

        return false;
    }

    /**
     * Get employee codes mapped by email for easy lookup
     *
     * @param  array  $emails  Array of email addresses
     * @return array Returns associative array with email as key and employee data as value
     */
    public static function getEmployeeCodesMap(array $emails): array
    {
        $result = self::getEmployeeCodes($emails);
        $map = [];

        if ($result !== false && is_array($result)) {
            foreach ($result as $employee) {
                if (isset($employee['email'])) {
                    $map[$employee['email']] = $employee;
                }
            }
        }

        return $map;
    }
}
