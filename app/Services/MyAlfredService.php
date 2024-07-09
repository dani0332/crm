<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

            info('AlfredFollowUpEmail Starting');
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

            info('AlfredFollowUpEmail ---- Request Sent '.$customer->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                $isCustomer = $this->customerService->getCustomerCampaignFollowups($customer->customer_id);
                if ($isCustomer->campaign_followups < 3) {
                    $isCustomer->increment('campaign_followups');
                    $isCustomer->last_followup_sent_at = Carbon::now();
                    $isCustomer->save();
                }
            }

            info('AlfredFollowUpEmail ---- Received Code : '.$responseCode.' '.$customer->email);
            info('AlfredFollowUpEmail ---- response object : '.json_encode($response->object()).'--'.$customer->email);

        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            Log::error($responseCode);
        }

        return $responseCode;
    }

    public function getAlfredEligibleCustomers($data)
    {
        try {
            $username = config('constants.MA_V1_USERNAME');
            $password = config('constants.MA_V1_PASSWORD');
            $basicAuth = base64_encode("$username:$password");

            $response = Http::timeout(20)->retry(2, 3000)
                ->withHeaders([
                    'Authorization' => 'Basic '.$basicAuth,
                ])
                ->post(config('constants.MA_V1_ENDPOINT').'/internal/wfs/get-remaining-scratches', ['data' => $data]);

            if ($response->ok()) {
                $response = $response->object();

                if ($response->data) {
                    return $response;
                }
            }
        } catch (Exception $e) {
            Log::error('getAlfredEligibleCustomers Error: '.$e->getMessage().$e->getTraceAsString());
        }

        return null;
    }
}
