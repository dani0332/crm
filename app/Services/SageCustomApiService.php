<?php

namespace App\Services;

use App\Enums\SageEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SageCustomApiService
{
    private mixed $sageCustomApiPassword;
    private mixed $sageCustomApiUserName;
    private string $sageBaseUrl;
    private mixed $bearerToken = null;
    private mixed $maxAttempts = 10;

    public function __construct()
    {
        $this->sageCustomApiUserName = env('SAGE_300_CUSTOM_API_USERNAME');
        $this->sageCustomApiPassword = env('SAGE_300_CUSTOM_API_USER_PASSWORD');
        $this->sageBaseUrl = env('SAGE_300_BASE_URL');
        $this->bearerToken = Cache::store('redis')->get(SageEnum::SAGE_CUSTOM_API_TOKEN_CACHE_KEY) ?? $this->getToken();
    }

    public function getToken()
    {
        if ($this->bearerToken) {
            return $this->bearerToken;
        }

        $loginUrl = $this->sageBaseUrl.SageEnum::SAGE_CUSTOM_API_GET_TOKEN_ENDPOINT;
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($loginUrl, [
            'username' => $this->sageCustomApiUserName,
            'password' => $this->sageCustomApiPassword,
        ]);
        if ($response->successful()) {
            Cache::store('redis')->put(SageEnum::SAGE_CUSTOM_API_TOKEN_CACHE_KEY, $response->body(), 60 * 30);
            $this->bearerToken = Cache::store('redis')->get(SageEnum::SAGE_CUSTOM_API_TOKEN_CACHE_KEY);

            return $this->bearerToken;
        } else {
            return null;
        }
    }

    public function getAPInvoicePaymentScheduleByBatchNumber($batchNumber)
    {
        $responseData = [
            'error' => null,
            'response' => null,
            'status' => false,
        ];
        $currentAttempts = 0;
        while (! $this->bearerToken && $this->maxAttempts >= $this->attempts) {
            $this->bearerToken = $this->getToken();
            $currentAttempts++;
        }
        if ($this->maxAttempts < $currentAttempts) {
            $responseData['error'] = 'execution timeout! Please try again later.';

            return $responseData;
        }
        $getAPInvoicePaymentScheduleUrl = $this->sageBaseUrl.SageEnum::SAGE_CUSTOM_API_GET_AP_PAYMENT_SCHEDULE_ENDPOINT.$batchNumber;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
        ])->get($getAPInvoicePaymentScheduleUrl);

        if ($response->successful()) {
            $responseData['response'] = $response->object();
            $responseData['status'] = true;

            return $responseData;
        } elseif ($response->failed()) {
            $responseData['response'] = $response->object();
            $responseData['error'] = $response->object()?->message;
            if ($responseData['error'] == SageEnum::SAGE_CUSTOM_API_INVALID_TOKEN_MESSAGE) {
                $currentAttempts++;
                return $this->getAPInvoicePaymentScheduleByBatchNumber($batchNumber);
            }

            return $responseData;
        }
    }

    public function updateAPInvoicePaymentSchedule($batchNumber, $aPInvoicePaymentsSchedule)
    {
        $responseData = [
            'error' => null,
            'response' => null,
            'url' => null,
            'status' => false,
        ];
        $currentAttempts = 0;
        if ($this->maxAttempts < $currentAttempts) {
            $responseData['error'] = 'execution timeout! Please try again later.';

            return $responseData;
        }
        $updateAPInvoicePaymentScheduleUrl = $this->sageBaseUrl.SageEnum::SAGE_CUSTOM_API_UPDATE_AP_PAYMENT_SCHEDULE_ENDPOINT.$batchNumber;
        $responseData['url'] = $updateAPInvoicePaymentScheduleUrl;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->bearerToken,
            'Content-Type' => 'application/json',
        ])->put($updateAPInvoicePaymentScheduleUrl, $aPInvoicePaymentsSchedule);

        if ($response->successful()) {
            $responseData['response'] = $response->object();
            $responseData['status'] = true;

            return $responseData;
        } elseif ($response->failed()) {
            if ($response->badRequest()) {
                $responseData['response'] = $response->object();
                $responseData['error'] = $response->object()?->title  ?? $response->object()?->messsage ?? 'Something went wrong!';
                return $responseData;
            }

            $responseData['response'] = $response->object();
            $responseData['error'] = $response->object()?->title ?? 'Something went wrong!';
            if ($responseData['error'] == SageEnum::SAGE_CUSTOM_API_INVALID_TOKEN_MESSAGE) {
                $currentAttempts++;
                return $this->updateAPInvoicePaymentSchedule($batchNumber, $aPInvoicePaymentsSchedule);
            }

            return $responseData;
        }

    }
}
