<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SageCustomApiService
{
    private mixed $sageCustomApiPassword;
    private mixed $sageCustomApiUserName;
    private string $sageBaseUrl;

    public function __construct()
    {
        $this->sageCustomApiUserName = env('SAGE_300_CUSTOM_API_USERNAME');
        $this->sageCustomApiPassword = env('SAGE_300_CUSTOM_API_USER_PASSWORD');
        $this->sageBaseUrl = env('SAGE_300_BASE_URL');
    }
    public function getToken()
    {
        $loginUrl = $this->sageBaseUrl.'/SageInvoiceAPI/api/User/Login';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($loginUrl, [
            'username' => $this->sageCustomApiUserName,
            'password' => $this->sageCustomApiPassword,
        ]);
        if ($response->successful()) {
            return $response->body();
        }else{
            return null;
        }
    }
}
