<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class MyAlfredService
{
    private $customerService;
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function sendingAlfredFollowupEmail($customer)
    {
        $emailTemplateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_FOLLOWUP_TEMPLATE)->first();
        $apiKey = config('constants.SENDINBLUE_KEY');
        $url = config('constants.SIB_URL');
        try {

            LoggerService::info('AlfredFollowUpEmail Starting');
            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ];
            $body = [
                'to' => [[
                    'email' => $customer->email,
                    'name' => $customer->name,
                ]],
                'templateId' => (int) $emailTemplateId->value,
                'params' => ['email' => $customer->email, 'customerName' => $customer->name],
            ];
            $response = Http::withHeaders($headers)
                ->post($url, $body);

            LoggerService::info('AlfredFollowUpEmail ---- Request Sent '.$customer->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                $isCustomer = $this->customerService->getCustomerCampaignFollowups($customer->customer_id);
                if ($isCustomer->campaign_followups < 3) {
                    $isCustomer->increment('campaign_followups');
                    $isCustomer->last_followup_sent_at = Carbon::now();
                    $isCustomer->save();
                }
            }

            LoggerService::info('AlfredFollowUpEmail ---- Received Code : '.$responseCode.' '.$customer->email);
            LoggerService::info('AlfredFollowUpEmail ---- response object : '.json_encode($response->object()).'--'.$customer->email);

        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            LoggerService::error('Error sending Alfred follow-up email: ' . $ex->getMessage(), [
                'customer_email' => $customer->email,
                'response_code' => $responseCode,
                'trace' => $ex->getTraceAsString()
            ]);
        }

        return $responseCode;
    }

    public function getAlfredEligibleCustomers($data)
    {
        try {
            $username = config('constants.MA_V1_USERNAME');
            $password = config('constants.MA_V1_PASSWORD');
            $basicAuth = base64_encode("$username:$password");

            LoggerService::info('Requesting eligible customers from Alfred API', [
                'data' => $data
            ]);

            $response = Http::timeout(20)->retry(2, 3000)
                ->withHeaders([
                    'Authorization' => 'Basic '.$basicAuth,
                ])
                ->post(config('constants.MA_V1_ENDPOINT').'/internal/wfs/get-remaining-scratches', ['data' => $data]);

            if ($response->ok()) {
                $response = $response->object();

                if ($response->data) {
                    LoggerService::info('Successfully retrieved eligible customers from Alfred API');
                    return $response;
                }
            }

            LoggerService::warning('No eligible customers found in Alfred API response', [
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);
        } catch (Exception $e) {
            LoggerService::error('getAlfredEligibleCustomers Error: '.$e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }
}
